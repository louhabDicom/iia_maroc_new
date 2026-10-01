<?php

namespace App\Casts;

use App\Enums\Locale;

/**
 * Shared decoding and fallback logic for the translation casts.
 *
 * Every translatable column has the same failure modes, so they are solved once
 * here rather than in each cast:
 *
 *  - the raw database value may be a JSON string, an array, or already null
 *  - malformed JSON must not throw during a page render
 *  - a blank translation must not shadow a real one
 *  - the fallback order must be explicit, never "first key in the array", which
 *    depends on JSON key order and changes meaning when a row is re-saved
 */
final class TranslationPayload
{
    /**
     * Normalise a raw column value into a locale => text array.
     *
     * @return array<string, string>
     */
    public static function toArray(mixed $value): array
    {
        if (is_string($value)) {
            // Some drivers (and every sqlite/MySQL JSON text read) hand back a
            // string. json_decode with no error check would return null and
            // hide the corruption.
            $decoded = json_decode($value, true);

            if (! is_array($decoded)) {
                return [];
            }

            $value = $decoded;
        }

        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach (Locale::cases() as $locale) {
            $text = $value[$locale->value] ?? null;

            if (is_string($text) && trim($text) !== '') {
                $out[$locale->value] = $text;
            }
        }

        return $out;
    }

    /**
     * Resolve the best available text for a locale.
     *
     * @param  array<string, string>  $translations
     */
    public static function resolve(array $translations, string $locale): ?string
    {
        if ($translations === []) {
            return null;
        }

        $fallback = (string) config('app.fallback_locale', 'fr');

        return $translations[$locale]
            ?? $translations[$fallback]
            // Deliberately locale-ordered, not insertion-ordered: with only one
            // translation present, Arabic visitors get Arabic.
            ?? $translations[Locale::French->value]
            ?? $translations[Locale::English->value]
            ?? $translations[Locale::Arabic->value]
            ?? null;
    }

    /**
     * @param  array<string, mixed>  $translations
     */
    public static function encode(array $translations): string
    {
        return (string) json_encode(
            $translations,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }
}
