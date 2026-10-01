<?php

namespace Database\Factories;

use App\Enums\OtpDriver;
use App\Enums\OtpPurpose;
use App\Models\OtpCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OtpCode>
 */
class OtpCodeFactory extends Factory
{
    protected $model = OtpCode::class;

    /**
     * The plaintext is only available here; the model stores a salted hash, so
     * a test that needs a working code has to go through hashCode().
     */
    public function definition(): array
    {
        return [
            'purpose' => OtpPurpose::Registration,
            'identifier' => '+2126'.fake()->unique()->numerify('########'),
            'driver' => OtpDriver::Log,
            'code_hash' => '',
            'code_salt' => '',
            'length' => 6,
            'attempts' => 0,
            'max_attempts' => 5,
            'expires_at' => now()->addMinutes(10),
            'consumed_at' => null,
            'last_sent_at' => now(),
            'request_ip' => '127.0.0.1',
            'provider_message_id' => 'log-'.fake()->bothify('????????????'),
        ];
    }

    /**
     * A row whose hash actually matches a known code.
     */
    public function withCode(string $code = '123456'): static
    {
        $hashes = OtpCode::hashCode($code);

        return $this->state(fn (): array => [
            'code_hash' => $hashes['code_hash'],
            'code_salt' => $hashes['code_salt'],
            'length' => strlen($code),
        ]);
    }

    public function forPurpose(OtpPurpose $purpose, string $identifier): static
    {
        return $this->state(fn (): array => [
            'purpose' => $purpose,
            'identifier' => $identifier,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    public function consumed(): static
    {
        return $this->state(fn (): array => [
            'consumed_at' => now()->subSecond(),
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (): array => [
            'attempts' => 5,
            'max_attempts' => 5,
        ]);
    }
}
