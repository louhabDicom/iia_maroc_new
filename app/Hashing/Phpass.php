<?php

declare(strict_types=1);

namespace App\Hashing;

/**
 * Verifier for the Portable PHP password hashing framework (phpass).
 *
 * The 2024 WordPress site stored most of its passwords with phpass, whose
 * `$P$` / `$H$` hashes are not bcrypt and cannot be read by `password_verify()`.
 * The legacy importer keeps those hashes verbatim, so this re-implements
 * phpass's `crypt_private()` closely enough to verify them; on the first
 * successful check the caller upgrades the row to bcrypt (see
 * {@see \App\Hashing\LegacyBcryptHasher} and the sign-in controller).
 *
 * The algorithm is the reference one from Openwall's phpass 0.5:
 * iterated MD5 over (salt . password), then a bespoke base64 of the digest.
 * Only verification is implemented — new passwords are always bcrypt.
 */
final class Phpass
{
    /** The phpass base64 alphabet, ordered by value. */
    private const ITOA64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * Whether the hash was produced by phpass rather than bcrypt/argon.
     */
    public static function isPhpass(string $hash): bool
    {
        return str_starts_with($hash, '$P$') || str_starts_with($hash, '$H$');
    }

    /**
     * Constant-time check of a plaintext password against a phpass hash.
     */
    public static function check(string $password, string $hash): bool
    {
        return hash_equals($hash, self::cryptPrivate($password, $hash));
    }

    /**
     * Recompute the hash from the setting (the first 12 bytes carrying the
     * prefix, iteration count and salt) and the password.
     */
    private static function cryptPrivate(string $password, string $setting): string
    {
        $id = substr($setting, 0, 3);

        if ($id !== '$P$' && $id !== '$H$') {
            return '*0';
        }

        $countLog2 = strpos(self::ITOA64, $setting[3]);

        if ($countLog2 === false || $countLog2 < 7 || $countLog2 > 30) {
            return '*0';
        }

        $salt = substr($setting, 4, 8);

        if (strlen($salt) !== 8) {
            return '*0';
        }

        // 2 ** countLog2 rounds of MD5 over the running digest and password.
        $count = 1 << $countLog2;
        $hash = md5($salt.$password, true);

        do {
            $hash = md5($hash.$password, true);
        } while (--$count);

        return substr($setting, 0, 12).self::encode64($hash, 16);
    }

    /**
     * phpass's base64 variant: little chunks of the digest emitted 6 bits at a
     * time, least significant first. It is not RFC 4648 and must match the
     * reference byte-for-byte or the comparison fails.
     */
    private static function encode64(string $input, int $count): string
    {
        $output = '';
        $i = 0;

        do {
            $value = ord($input[$i++]);
            $output .= self::ITOA64[$value & 0x3F];

            if ($i < $count) {
                $value |= ord($input[$i]) << 8;
            }

            $output .= self::ITOA64[($value >> 6) & 0x3F];

            if ($i++ >= $count) {
                break;
            }

            if ($i < $count) {
                $value |= ord($input[$i]) << 16;
            }

            $output .= self::ITOA64[($value >> 12) & 0x3F];

            if ($i++ >= $count) {
                break;
            }

            $output .= self::ITOA64[($value >> 18) & 0x3F];
        } while ($i < $count);

        return $output;
    }
}