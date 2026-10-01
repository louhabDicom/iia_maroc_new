<?php

namespace App\Casts;

use App\Enums\Locale;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

/**
 * Translatable JSON array column, e.g. ticket `includes`.
 *
 * Returns a flat list of strings for the active locale. Items may be plain
 * strings or `{fr, en, ar}` objects, because the two shapes appear in the
 * spreadsheet: "Accès aux ateliers" as a string, and a keyed object when a
 * bullet needs a per-language link.
 *
 * Unlike {@see TranslatedString} there is no cross-locale fallback. If the
 * active locale is missing, this returns an empty list rather than another
 * language's bullets, so a French page can never show Arabic content by
 * accident. That is a deliberate difference: one wrong sentence is a visible
 * bug, a list of wrong bullets reads as authoritative.
 */
final class TranslatedList implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<int, string>
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        // A list payload holds arrays, so it is decoded with the same guard as
        // TranslatedString but without the "is this string or array" shortcut:
        // the per-locale value itself may be a string when the editor typed one.
        $raw = is_array($value)
            ? $value
            : (is_string($value) ? (json_decode($value, true) ?: []) : []);

        $locale = App::getLocale();
        $list = $raw[$locale] ?? null;

        if ($list === null) {
            return [];
        }

        if (is_string($list)) {
            // Tolerate a single string where a list was expected, which is what
            // an admin typing into a plain text field produces.
            return trim($list) === '' ? [] : [trim($list)];
        }

        if (! is_array($list)) {
            return [];
        }

        $out = [];

        foreach ($list as $item) {
            if (is_string($item)) {
                $out[] = trim($item);

                continue;
            }

            if (! is_array($item)) {
                continue;
            }

            $nested = TranslationPayload::toArray($item);
            $text = $nested[$locale] ?? TranslationPayload::resolve($nested, $locale);

            if ($text !== null) {
                $out[] = $text;
            }
        }

        return array_values(array_filter($out, static fn (string $item): bool => $item !== ''));
    }

    /**
     * Idempotent like {@see TranslatedString::set()}: accepts a map, an
     * already-encoded JSON string, or null.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        if (is_array($value)) {
            $map = $value;
        } else {
            $decoded = is_string($value) ? json_decode($value, true) : null;
            $map = is_array($decoded) ? $decoded : null;
        }

        if ($map === null) {
            // A bare string: treat it as a one-item list in the active locale.
            $map = [App::getLocale() => [(string) $value]];
        }

        $out = [];

        foreach (Locale::cases() as $locale) {
            if (! isset($map[$locale->value])) {
                continue;
            }

            $out[$locale->value] = array_values(array_map(
                static fn ($item) => is_array($item)
                    ? TranslationPayload::toArray($item)
                    : (string) $item,
                (array) $map[$locale->value]
            ));
        }

        return [$key => $out === [] ? null : TranslationPayload::encode($out)];
    }
}
