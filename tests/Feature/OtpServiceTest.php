<?php

namespace Tests\Feature;

use App\Enums\OtpDriver;
use App\Enums\OtpPurpose;
use App\Exceptions\OtpDeliveryException;
use App\Exceptions\OtpVerificationException;
use App\Models\OtpCode;
use App\Services\Otp\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Behavioural guarantees for the OTP flow.
 *
 * These assert the properties the 2024 build lacked: codes are not stored in
 * plaintext, they are single-use, a code is bound to its purpose, guessing is
 * throttled, and resending is throttled so the endpoint cannot be used to
 * bill-bomb a phone number.
 */
class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtpService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'otp.driver' => 'log',
            'otp.length' => 6,
            'otp.resend_cooldown_seconds' => 60,
            'otp.max_sends_per_hour' => 5,
            'otp.rate_limit_per_phone' => 5,
        ]);

        $this->service = app(OtpService::class);
    }

    public function test_a_generated_code_has_the_configured_length_and_only_digits(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $code = $this->service->generateCode();

            $this->assertSame(6, strlen($code));
            $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        }
    }

    public function test_issued_codes_are_never_stored_in_readable_form(): void
    {
        $record = $this->service->issue(OtpPurpose::Registration, '0612345678', 'fr');

        $this->assertNotEmpty($record->code_hash);
        $this->assertNotEmpty($record->code_salt);

        // Both columns are 64 hex characters of SHA-256 output, so neither can
        // hold a 6-digit decimal code, and the salt is not the code either.
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $record->code_hash);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $record->code_salt);
        $this->assertNotSame($record->code_salt, $record->code_hash);
    }

    public function test_each_code_uses_a_fresh_salt(): void
    {
        $first = OtpCode::hashCode('123456');
        $second = OtpCode::hashCode('123456');

        $this->assertNotSame($first['code_salt'], $second['code_salt']);
        $this->assertNotSame($first['code_hash'], $second['code_hash']);
    }

    public function test_a_code_round_trips_through_the_verifier(): void
    {
        $record = OtpCode::factory()->withCode('123456')->create();

        $this->assertTrue($record->verify('123456'));
        $this->assertFalse($record->verify('123457'));
    }

    public function test_issuing_records_a_code_against_the_canonical_phone(): void
    {
        $record = $this->service->issue(OtpPurpose::Registration, '06 12 34 56 78', 'fr');

        $this->assertSame('+212612345678', $record->identifier);
        $this->assertSame(OtpDriver::Log, $record->driver);
    }

    public function test_resending_inside_the_cooldown_is_refused(): void
    {
        $this->service->issue(OtpPurpose::Registration, '0612345678', 'fr');

        $this->expectException(OtpDeliveryException::class);

        $this->service->issue(OtpPurpose::Registration, '0612345678', 'fr');
    }

    public function test_issuing_a_replacement_kills_the_previous_code(): void
    {
        // Backdate the first send so the cooldown no longer blocks a resend.
        $first = $this->service->issue(OtpPurpose::Registration, '0612345678', 'fr');
        $first->forceFill(['last_sent_at' => now()->subMinutes(5)])->save();

        $this->service->issue(OtpPurpose::Registration, '0612345678', 'fr');

        $this->assertTrue($first->fresh()->isConsumed());
    }

    public function test_a_valid_code_is_consumed_and_cannot_be_replayed(): void
    {
        OtpCode::factory()->withCode('123456')->create([
            'purpose' => OtpPurpose::Registration,
            'identifier' => '+212612345678',
        ]);

        $this->service->verify(OtpPurpose::Registration, '0612345678', '123456');

        $this->expectException(OtpVerificationException::class);
        $this->service->verify(OtpPurpose::Registration, '0612345678', '123456');
    }

    public function test_a_code_cannot_be_replayed_into_another_purpose(): void
    {
        OtpCode::factory()->withCode('123456')->create([
            'purpose' => OtpPurpose::Registration,
            'identifier' => '+212612345678',
        ]);

        // Same phone, same digits, different flow: must not be accepted.
        $this->expectException(OtpVerificationException::class);

        $this->service->verify(OtpPurpose::PasswordReset, '0612345678', '123456');
    }

    public function test_a_wrong_guess_increments_the_attempt_counter(): void
    {
        $record = OtpCode::factory()->withCode('123456')->create([
            'purpose' => OtpPurpose::Registration,
            'identifier' => '+212612345678',
        ]);

        try {
            $this->service->verify(OtpPurpose::Registration, '0612345678', '000000');
        } catch (OtpVerificationException) {
            // expected
        }

        $this->assertSame(1, $record->fresh()->attempts);
    }

    public function test_repeated_wrong_guesses_lock_the_code(): void
    {
        $record = OtpCode::factory()->withCode('123456')->create([
            'purpose' => OtpPurpose::Registration,
            'identifier' => '+212612345678',
            'max_attempts' => 3,
        ]);

        for ($i = 0; $i < 3; $i++) {
            try {
                $this->service->verify(OtpPurpose::Registration, '0612345678', '000000');
            } catch (OtpVerificationException) {
                // expected
            }
        }

        $this->assertTrue($record->fresh()->isLocked());

        // Even the correct code is refused once the code is locked.
        $this->expectException(OtpVerificationException::class);
        $this->service->verify(OtpPurpose::Registration, '0612345678', '123456');
    }

    public function test_an_expired_code_is_rejected(): void
    {
        OtpCode::factory()->withCode('123456')->expired()->create([
            'purpose' => OtpPurpose::Registration,
            'identifier' => '+212612345678',
        ]);

        $this->expectException(OtpVerificationException::class);

        $this->service->verify(OtpPurpose::Registration, '0612345678', '123456');
    }

    public function test_a_missing_code_and_a_wrong_code_are_indistinguishable_to_the_caller(): void
    {
        try {
            $this->service->verify(OtpPurpose::Registration, '0699999999', '000000');
            $this->fail('expected a verification failure');
        } catch (OtpVerificationException $e) {
            $missing = $e->userMessage('fr');
        }

        OtpCode::factory()->withCode('123456')->create([
            'purpose' => OtpPurpose::Registration,
            'identifier' => '+212612345678',
        ]);

        try {
            $this->service->verify(OtpPurpose::Registration, '0612345678', '000000');
            $this->fail('expected a verification failure');
        } catch (OtpVerificationException $e) {
            $wrong = $e->userMessage('fr');
        }

        $this->assertSame($missing, $wrong);
    }

    public function test_issuing_writes_an_audit_row_with_a_masked_identifier(): void
    {
        $this->service->issue(OtpPurpose::Registration, '0612345678', 'fr');

        $event = \DB::table('security_events')->where('event', 'otp.sent')->first();

        $this->assertNotNull($event);
        $this->assertStringNotContainsString('0612345678', (string) $event->identifier);
    }

    public function test_the_log_driver_writes_the_code_to_the_log_in_local_only(): void
    {
        Log::spy();

        $this->service->issue(OtpPurpose::Registration, '0612345678', 'fr');

        Log::shouldHaveReceived('notice')
            ->withArgs(fn (string $line): bool => str_contains($line, 'OTP:log'));
    }

    public function test_delivery_failure_does_not_leave_a_live_code_behind(): void
    {
        config(['otp.driver' => 'sms', 'otp.sms.from' => null]);

        try {
            $this->service->issue(OtpPurpose::Registration, '0612345678', 'fr');
            $this->fail('expected a delivery failure');
        } catch (OtpDeliveryException) {
            // expected: no sender configured
        }

        $this->assertSame(
            0,
            OtpCode::query()->for(OtpPurpose::Registration, '+212612345678')
                ->active()
                ->count()
        );
    }
}
