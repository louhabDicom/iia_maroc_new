<?php

declare(strict_types=1);

namespace App\Services\Payment;

/**
 * The CMI e-Payment 3D Secure hash.
 *
 * CMI does not sign with an asymmetric key: both sides build the same
 * canonical string and hash it with a shared secret. The algorithm is fixed by
 * the gateway and is reproduced here byte-for-byte, because a single differing
 * character produces a hash the gateway rejects and no error explaining why.
 *
 * The construction, in order:
 *
 *   1. Take every posted parameter except `hash` and `encoding`. The exclusion
 *      is case-insensitive — CMI sends `HASH` in capitals.
 *   2. Sort the parameter *names* with a natural, case-insensitive sort
 *      (PHP's `natcasesort`). The order must match CMI's or the two sides
 *      build different strings.
 *   3. Concatenate the *values* only — not `name=value` — separated by `|`.
 *   4. Escape each value: backslash first, then `|`. The order matters; doing
 *      it the other way round double-escapes the separator.
 *   5. Append the store key, escaped by the same rules.
 *   6. SHA-512, then base64 of the *raw* hex bytes.
 *
 * Step 6 is the one that looks wrong and is not: `base64(pack('H*', $hex))`
 * decodes the 128 hex characters into 64 raw bytes and encodes those. Base64
 * encoding the hex string itself is the single most common integration bug
 * and produces a well-formed hash that never matches.
 */
final class CmiHasher
{
    /**
     * Parameters excluded from the hash.
     *
     * `hash` is the signature itself. `encoding` is excluded by CMI's
     * specification because it describes the transport rather than the
     * transaction, and the same value is sent on the request and the callback.
     */
    private const EXCLUDED = ['hash', 'encoding'];

    /**
     * Build the hash for a parameter set.
     *
     * @param  array<string, mixed>  $params
     */
    public function hash(array $params, string $storeKey): string
    {
        return $this->sign($this->canonicalise($params, $storeKey));
    }

    /**
     * Reduce a parameter set to the exact string CMI hashes.
     *
     * Exposed separately from {@see hash()} because the same value has to be
     * reproduced by hand when diagnosing a rejected callback, and a test that
     * can assert on the canonical string pinpoints a mismatch far faster than
     * one comparing only the final digest.
     *
     * @param  array<string, mixed>  $params
     */
    public function canonicalise(array $params, string $storeKey): string
    {
        $names = array_keys($params);

        // natcasesort, not ksort: "item10" must sort after "item9", and
        // "Hash" must land next to "hash" rather than at the top of the
        // alphabet. ksort on byte order would disagree with CMI here.
        natcasesort($names);

        $parts = [];

        foreach ($names as $name) {
            if (in_array(strtolower((string) $name), self::EXCLUDED, true)) {
                continue;
            }

            $parts[] = $this->escape($this->normalise($params[$name]));
        }

        $parts[] = $this->escape($storeKey);

        return implode('|', $parts);
    }

    /**
     * SHA-512 the canonical string, then base64 the raw digest bytes.
     */
    public function sign(string $canonical): string
    {
        return base64_encode((string) pack('H*', hash('sha512', $canonical)));
    }

    /**
     * Constant-time comparison of a received hash against the expected one.
     *
     * `hash_equals` rather than `===`: the legacy callback used `==`, which
     * short-circuits on the first differing byte and leaks, through response
     * timing, how much of a forged hash was correct.
     */
    public function verify(array $params, string $storeKey, ?string $received): bool
    {
        if (! is_string($received) || $received === '') {
            return false;
        }

        return hash_equals($this->hash($params, $storeKey), $received);
    }

    /**
     * Reduce a posted value to the string the gateway hashed.
     *
     * A trailing newline is stripped because some proxies append one, and
     * `html_entity_decode` because CMI's own callback re-encodes special
     * characters. The 2024 build did this inconsistently between
     * `SendData.php` (no decode) and `Ok-Fail.php` (decode), so a name
     * containing an ampersand produced a hash mismatch that looked like a
     * tampering alert.
     */
    private function normalise(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value === null) {
            return '';
        }

        if (is_array($value)) {
            // A repeated field is not part of the CMI contract. Rendering it
            // as JSON is deterministic; ignoring it silently would make the
            // hash depend on how PHP happened to parse the body.
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $string = (string) $value;

        $string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
        $string = (string) preg_replace('/\n$/', '', $string);

        return trim($string);
    }

    /**
     * Escape a value for the hash: backslashes first, then pipes.
     */
    private function escape(string $value): string
    {
        return str_replace('|', '\\|', str_replace('\\', '\\\\', $value));
    }
}