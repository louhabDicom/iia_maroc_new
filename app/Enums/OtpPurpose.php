<?php

namespace App\Enums;

/**
 * OTP purpose scopes.
 *
 * A code minted for one flow must never be replayable into another, so every
 * code is bound to a purpose and the verifier requires a matching purpose.
 */
enum OtpPurpose: string
{
    case Registration = 'registration';
    case Login = 'login';
    case PasswordReset = 'password_reset';
    case PhoneChange = 'phone_change';

    public function label(string $locale = 'fr'): string
    {
        return __("otp.purpose.{$this->value}", [], $locale);
    }

    /**
     * Codes for sensitive purposes get a shorter TTL and fewer attempts, so
     * guessing a password-reset code is harder than finishing registration.
     */
    public function ttlSeconds(): int
    {
        return match ($this) {
            self::Registration, self::Login => (int) config('otp.ttl_seconds', 600),
            self::PasswordReset, self::PhoneChange => 300,
        };
    }

    public function maxAttempts(): int
    {
        return match ($this) {
            self::Registration, self::Login => (int) config('otp.max_attempts', 5),
            self::PasswordReset, self::PhoneChange => 3,
        };
    }

    /**
     * Whether this purpose must be sent to a phone (SMS) rather than an
     * inbox. Phone-bound purposes are the ones the brief asked to protect.
     */
    public function requiresPhone(): bool
    {
        return true;
    }
}
