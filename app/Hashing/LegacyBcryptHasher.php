<?php

declare(strict_types=1);

namespace App\Hashing;

use Illuminate\Hashing\BcryptHasher;

/**
 * The application's default hasher: bcrypt for new passwords, with read
 * support for the hashes carried over from the 2024 WordPress site.
 *
 * bcrypt itself refuses anything that is not bcrypt — with algorithm
 * verification enabled it throws "This password does not use the Bcrypt
 * algorithm", and without it `password_verify()` simply returns false. Two
 * legacy shapes need translating before bcrypt can see them:
 *
 *  - phpass `$P$` / `$H$` hashes, verified by {@see Phpass};
 *  - WordPress bcrypt written as `$wp$2y$...`, where PHP does not recognise
 *    the `$wp$` wrapper. The wrapper is three characters, so the hash is
 *    `$wp` followed by a normal `$2y$...`.
 *
 * `make()` is untouched, so every new password is a real bcrypt hash.
 * `needsRehash()` reports any translated hash as needing a rehash, so the
 * sign-in controller upgrades it to bcrypt once the password is known to be
 * correct and legacy rows converge without a reset.
 */
class LegacyBcryptHasher extends BcryptHasher
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function check(#[\SensitiveParameter] $value, $hashedValue, array $options = []): bool
    {
        if (! is_string($hashedValue)) {
            return parent::check($value, $hashedValue, $options);
        }

        if (Phpass::isPhpass($hashedValue)) {
            return Phpass::check((string) $value, $hashedValue);
        }

        return parent::check($value, $this->unwrapLegacyBcrypt($hashedValue), $options);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function needsRehash($hashedValue, array $options = []): bool
    {
        if (! is_string($hashedValue)) {
            return parent::needsRehash($hashedValue, $options);
        }

        if (self::isLegacy($hashedValue)) {
            return true;
        }

        return parent::needsRehash($hashedValue, $options);
    }

    /**
     * Whether the hash predates bcrypt or was stored in a shape bcrypt cannot
     * read directly.
     */
    private static function isLegacy(string $hash): bool
    {
        return Phpass::isPhpass($hash)
            || str_starts_with($hash, '$wp$')
            || preg_match('/^2[aby]\$\d{2}\$/', $hash) === 1;
    }

    /**
     * Turn a stored legacy bcrypt hash into one `password_verify()` accepts:
     * drop the `$wp$` wrapper, and restore a leading `$` lost by an earlier
     * importer that stripped one character too many.
     */
    private function unwrapLegacyBcrypt(string $hash): string
    {
        if (str_starts_with($hash, '$wp$')) {
            return substr($hash, 3);
        }

        if (preg_match('/^2[aby]\$\d{2}\$/', $hash) === 1) {
            return '$'.$hash;
        }

        return $hash;
    }
}