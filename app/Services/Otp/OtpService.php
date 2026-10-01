<?php

namespace App\Services\Otp;

use App\Enums\OtpDriver;
use App\Enums\OtpPurpose;
use App\Exceptions\OtpDeliveryException;
use App\Exceptions\OtpVerificationException;
use App\Models\OtpCode;
use App\Services\Security\RateLimiter;
use App\Services\Security\SecurityEventLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Issues and verifies one-time codes.
 *
 * Every code is generated here so there is exactly one place that decides
 * length, entropy and hashing. Nothing else in the application may create an
 * `otp_codes` row.
 *
 * Anti-abuse properties, in the order they matter:
 *
 *  1. Issuing is rate limited per phone (cooldown + hourly cap) and per IP, so
 *     the endpoint cannot be used to bill-bomb a phone number.
 *  2. Verification is rate limited per code, per identifier and per IP, and
 *     locks a code after a set number of wrong guesses.
 *  3. Issuing invalidates any previous outstanding code, so there is at most one
 *     live code per (purpose, identifier) and an intercepted earlier code is
 *     dead the moment a new one is sent.
 *  4. Codes are stored salted+hashed, never in plaintext.
 */
class OtpService
{
    public function __construct(
        private readonly SecurityEventLogger $events,
        private readonly RateLimiter $limiter,
    ) {}

    /**
     * Generate a code from a CSPRNG.
     *
     * Rejection sampling keeps the distribution uniform: a naive
     * `random_int(0, 9)` per digit is fine, but `hexdec(substr(random_bytes(1)))`
     * is not, and getting this wrong is a real source of weak OTP entropy.
     */
    public function generateCode(?int $length = null): string
    {
        $length ??= (int) config('otp.length', 6);
        $code = '';

        while (strlen($code) < $length) {
            $bytes = random_bytes($length);
            foreach (str_split($bytes) as $byte) {
                // Keep only 0-9 by discarding anything in the top quarter of
                // the byte range, which avoids modulo bias.
                $n = ord($byte);
                if ($n < 250) {
                    $code .= (string) ($n % 10);
                    if (strlen($code) === $length) {
                        break;
                    }
                }
            }
        }

        return $code;
    }

    /**
     * Issue a code and dispatch it.
     *
     * @throws OtpDeliveryException when throttled or undeliverable
     */
    public function issue(
        OtpPurpose $purpose,
        string $identifier,
        string $locale,
        ?string $ip = null,
    ): OtpCode {
        $identifier = $this->normaliseIdentifier($purpose, $identifier);
        $ip ??= $this->requestIp();

        $this->assertCanIssue($purpose, $identifier, $ip);

        // Only one live code at a time for this purpose + identifier.
        OtpCode::invalidateAll($purpose, $identifier);

        $plaintext = $this->generateCode();

        $record = DB::transaction(function () use ($purpose, $identifier, $plaintext, $ip): OtpCode {
            // One salt, one hash: the pair must be generated together.
            $hashes = OtpCode::hashCode($plaintext);

            return OtpCode::create([
                'purpose' => $purpose,
                'identifier' => $identifier,
                'driver' => $this->resolveDriverName(),
                'code_hash' => $hashes['code_hash'],
                'code_salt' => $hashes['code_salt'],
                'length' => strlen($plaintext),
                'attempts' => 0,
                'max_attempts' => $purpose->maxAttempts(),
                'expires_at' => now()->addSeconds($purpose->ttlSeconds()),
                'request_ip' => $ip,
            ]);
        });

        $channel = $this->channel();

        try {
            $messageId = $channel->send($identifier, $plaintext, $purpose, $locale);
        } catch (OtpDeliveryException $e) {
            // Do not leave a live code behind that the user can never receive.
            $record->forceFill(['consumed_at' => now()])->save();

            $this->events->log('otp.delivery_failed', [
                'level' => 'error',
                'identifier' => PhoneNumber::mask($identifier),
                'ip' => $ip,
                'context' => [
                    'purpose' => $purpose->value,
                    'driver' => $channel->name(),
                    'error' => $e->getMessage(),
                ],
            ]);

            throw $e;
        }

        $record->markSent($messageId);

        // The same bucket the cap above was checked against. Incrementing a
        // differently named bucket here is what previously made the hourly
        // ceiling unreachable.
        $this->limiter->hit($this->limiter->otpHourBucket($purpose->value), $identifier, $ip);

        $this->events->log('otp.sent', [
            'identifier' => PhoneNumber::mask($identifier),
            'ip' => $ip,
            'context' => [
                'purpose' => $purpose->value,
                'driver' => $channel->name(),
                'expires_at' => $record->expires_at->toIso8601String(),
            ],
        ]);

        return $record;
    }

    /**
     * Verify a submitted code.
     *
     * The lookup, the comparison and the consumption all happen inside one
     * transaction with the row locked. Reading the code first and consuming it
     * afterwards left a window in which two requests carrying the same valid
     * code both passed the comparison and both consumed it — a single-use code
     * that was in fact usable twice.
     *
     * @throws OtpVerificationException on any failure
     */
    public function verify(OtpPurpose $purpose, string $identifier, string $code, ?string $ip = null): OtpCode
    {
        $identifier = $this->normaliseIdentifier($purpose, $identifier);
        $ip ??= $this->requestIp();

        $this->limiter->assertWithin(
            $this->limiter->otpVerifyBucket($purpose->value),
            $identifier,
            $ip,
            (int) config('otp.rate_limit_per_phone', 5),
            300,
        );

        $outcome = DB::transaction(function () use ($purpose, $identifier, $code) {
            $record = OtpCode::query()
                ->for($purpose, $identifier)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $record) {
                return [null, OtpVerificationException::notFound()];
            }

            if ($record->isLocked()) {
                return [$record, OtpVerificationException::locked()];
            }

            if ($record->isConsumed()) {
                return [$record, OtpVerificationException::consumed()];
            }

            if ($record->isExpired()) {
                return [$record, OtpVerificationException::expired()];
            }

            if (! $record->verify($code)) {
                $record->recordFailedAttempt();

                return [$record, $record->isLocked()
                    ? OtpVerificationException::locked()
                    : OtpVerificationException::mismatch()];
            }

            // Valid. Burn every outstanding code for this purpose, not just this
            // one, so an intercepted earlier delivery cannot be replayed later.
            $record->markConsumed();
            OtpCode::invalidateAll($purpose, $identifier);

            return [$record, null];
        });

        /** @var OtpCode|null $record */
        [$record, $failure] = $outcome;

        if ($failure !== null) {
            // A missing code burns the same budget a wrong code would, so an
            // attacker cannot distinguish "no code issued" from "wrong guess"
            // by watching the response.
            $this->limiter->hit($this->limiter->otpGuessBucket($purpose->value), $identifier, $ip);

            if ($record !== null && ! $record->isConsumed() && $record->attempts > 0) {
                $this->events->log('otp.verify_failed', [
                    'level' => 'warning',
                    'identifier' => PhoneNumber::mask($identifier),
                    'ip' => $ip,
                    'context' => [
                        'purpose' => $purpose->value,
                        'attempts' => $record->attempts,
                        'locked' => $record->isLocked(),
                    ],
                ]);
            }

            throw $failure;
        }

        $this->events->log('otp.verified', [
            'identifier' => PhoneNumber::mask($identifier),
            'ip' => $ip,
            'context' => ['purpose' => $purpose->value],
        ]);

        // A correct code clears the guess counter for this identifier, so two
        // typos do not leave the next challenge throttled.
        $this->limiter->clear($this->limiter->otpGuessBucket($purpose->value), $identifier);

        return $record;
    }

    /**
     * Refuse to issue when the identifier or IP is over its budget.
     *
     * @throws OtpDeliveryException
     */
    private function assertCanIssue(OtpPurpose $purpose, string $identifier, ?string $ip): void
    {
        $cooldown = (int) config('otp.resend_cooldown_seconds', 60);

        $recent = OtpCode::query()
            ->for($purpose, $identifier)
            ->where('last_sent_at', '>=', now()->subSeconds($cooldown))
            ->exists();

        if ($recent) {
            $this->events->log('otp.throttled', [
                'level' => 'warning',
                'identifier' => PhoneNumber::mask($identifier),
                'ip' => $ip,
                'context' => ['reason' => 'cooldown', 'cooldown' => $cooldown],
            ]);

            throw new OtpDeliveryException(
                __('otp.throttled', ['seconds' => $cooldown]),
            );
        }

        $perHour = (int) config('otp.max_sends_per_hour', 5);

        $this->limiter->assertWithin(
            $this->limiter->otpHourBucket($purpose->value),
            $identifier,
            $ip,
            $perHour,
            3600,
        );
    }

    /**
     * `request()->ip()` is not always available — a queued job or a console
     * command has no request, and calling it there is a fatal error rather than
     * a null. The IP is used for rate limiting, so a null is degraded but safe.
     */
    private function requestIp(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        return request()->ip();
    }

    private function normaliseIdentifier(OtpPurpose $purpose, string $identifier): string
    {
        $type = $this->channel()->identifierType();

        return match ($type) {
            'phone' => PhoneNumber::normalise($identifier),
            'email' => strtolower(trim($identifier)),
            default => trim($identifier),
        };
    }

    private function channel(): \App\Contracts\OtpChannel
    {
        return app(\App\Contracts\OtpChannel::class);
    }

    private function resolveDriverName(): string
    {
        return $this->channel()->name();
    }
}
