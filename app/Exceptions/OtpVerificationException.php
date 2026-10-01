<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when a submitted code is wrong, expired, exhausted or already used.
 *
 * The user-facing message is intentionally identical for every one of those
 * cases, so an attacker cannot tell "expired" from "wrong digits" and use the
 * difference to learn how close a guess was.
 */
class OtpVerificationException extends RuntimeException
{
    public const REASON_MISMATCH = 'mismatch';

    public const REASON_EXPIRED = 'expired';

    public const REASON_CONSUMED = 'consumed';

    public const REASON_LOCKED = 'locked';

    public const REASON_NOT_FOUND = 'not_found';

    public const REASON_TOO_FREQUENT = 'too_frequent';

    public function __construct(public readonly string $reason = self::REASON_MISMATCH)
    {
        parent::__construct('The verification code is not valid.');
    }

    public static function mismatch(): self
    {
        return new self(self::REASON_MISMATCH);
    }

    public static function expired(): self
    {
        return new self(self::REASON_EXPIRED);
    }

    public static function consumed(): self
    {
        return new self(self::REASON_CONSUMED);
    }

    public static function locked(): self
    {
        return new self(self::REASON_LOCKED);
    }

    public static function notFound(): self
    {
        return new self(self::REASON_NOT_FOUND);
    }

    public static function tooFrequent(): self
    {
        return new self(self::REASON_TOO_FREQUENT);
    }

    /** Neutral message shown to the user regardless of the real reason. */
    public function userMessage(string $locale = 'fr'): string
    {
        return __('otp.invalid', [], $locale);
    }
}
