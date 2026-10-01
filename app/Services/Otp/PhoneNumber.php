<?php

namespace App\Services\Otp;

use Illuminate\Support\Str;

/**
 * Phone number canonicalisation.
 *
 * Every phone the system stores or compares goes through here, so that
 * "+212 6 12 34 56 78", "0612345678" and "00212612345678" are one identity.
 * Without this, OTP delivery breaks silently and the same person registers
 * twice to dodge the hourly send cap.
 *
 * Rules, Morocco first with a documented fallback for other regions:
 *
 *  - keep a leading +, drop every other non-digit
 *  - a leading 00 is an international prefix, so it becomes +
 *  - a number already starting with 212 is international
 *  - a national number keeps its trunk 0 in Morocco, which is dropped:
 *    0612345678 is 212 + 612345678, not 212 + 0612345678
 *  - anything else is rejected by returning an empty string, so an unparseable
 *    value cannot be stored and silently matched against something later
 */
final class PhoneNumber
{
    public const DEFAULT_COUNTRY_CODE = '212';

    /** National trunk prefix, present on every Moroccan landline and mobile. */
    private const NATIONAL_PREFIX = '0';

    /** @return string canonical form, e.g. "+212612345678", or "" if unparseable */
    public static function normalise(string $input): string
    {
        $trimmed = trim($input);

        if ($trimmed === '') {
            return '';
        }

        $hasPlus = Str::startsWith($trimmed, '+');
        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

        if ($digits === '') {
            return '';
        }

        // 00 is the international access code.
        if (Str::startsWith($digits, '00')) {
            return self::isPlausibleLength(substr($digits, 2))
                ? '+'.substr($digits, 2)
                : '';
        }

        if ($hasPlus) {
            return self::isPlausibleLength($digits) ? '+'.$digits : '';
        }

        // Already international, typed without the plus.
        if (Str::startsWith($digits, self::DEFAULT_COUNTRY_CODE)) {
            return self::isPlausibleLength($digits) ? '+'.$digits : '';
        }

        // National Moroccan number: 06xxxxxxxx mobile or 05xxxxxxxx landline.
        // The trunk 0 is a national artifact and must be dropped.
        if (Str::startsWith($digits, self::NATIONAL_PREFIX) && strlen($digits) === 10) {
            $national = substr($digits, 1);

            return self::isPlausibleLength($national)
                ? '+'.self::DEFAULT_COUNTRY_CODE.$national
                : '';
        }

        return '';
    }

    /**
     * Format for display. The locale does not change the digits, only the
     * grouping, so the output is intentionally identical for fr/en/ar.
     */
    public static function format(?string $canonical, ?string $locale = null): string
    {
        if (blank($canonical)) {
            return '';
        }

        $digits = ltrim((string) $canonical, '+');

        if (Str::startsWith($digits, self::DEFAULT_COUNTRY_CODE) && strlen($digits) === 12) {
            return '+'.self::DEFAULT_COUNTRY_CODE.' '.substr($digits, 3);
        }

        return $canonical;
    }

    /**
     * E.164 allows 8 to 15 digits after the plus.
     */
    public static function isValid(string $input): bool
    {
        return self::normalise($input) !== '';
    }

    /**
     * Mask for logs and audit rows.
     *
     * The country code stays visible because a reviewer needs to tell a
     * Moroccan number from a French one; everything else is hidden except the
     * last four digits, which is what support actually asks for.
     */
    public static function mask(?string $canonical): string
    {
        $digits = ltrim((string) $canonical, '+');

        if ($digits === '') {
            return '';
        }

        // Assumes a three-digit country code, which covers every number this
        // conference will realistically see.
        $country = substr($digits, 0, 3);
        $rest = substr($digits, 3);

        if (strlen($rest) <= 4) {
            return '+'.$country.str_repeat('*', strlen($rest));
        }

        return '+'.$country.str_repeat('*', strlen($rest) - 4).substr($rest, -4);
    }

    private static function isPlausibleLength(string $digits): bool
    {
        return strlen($digits) >= 8 && strlen($digits) <= 15;
    }
}
