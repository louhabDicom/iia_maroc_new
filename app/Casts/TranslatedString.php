<?php

namespace App\Casts;

use App\Enums\Locale;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

/**
 * Translatable JSON string column.
 *
 * Stored shape is `{"fr": "...", "en": "...", "ar": "..."}`. Reading resolves to
 * a single string for the active locale, with a deliberate fallback chain
 * (requested locale -> app fallback -> any populated value) so a missing
 * translation degrades to readable text instead of an empty string.
 *
 * The 2024 failure this replaces was not a missing key but a *missing
 * consistency*: `$lang['index']['calendrier']['day2']['gala']` was read while
 * `gala` only existed under `day1`, so French and Arabic rendered
 * "Undefined index: gala". Reading through one cast with a fallback chain makes
 * that class of bug impossible to express.
 *
 * Note on $value: for a custom cast Laravel hands over the raw database value,
 * not a decoded array, so decoding happens here. A cast that only does
 * `is_array($value)` silently returns null for every row.
 */
final class TranslatedString implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        $translations = TranslationPayload::toArray($value);

        if ($translations === []) {
            return null;
        }

        return TranslationPayload::resolve($translations, App::getLocale());
    }

    /**
     * Always write all locales so switching language never loses content.
     *
     * Accepts a `{fr, en, ar}` map, a plain string for the active locale, or an
     * already-encoded JSON object string. The last case matters: the set is
     * idempotent, because a caller holding the raw column value (a legacy
     * import, a seeder, a Filament edit) would otherwise produce
     * `{"fr":"{\"fr\":\"…\"}"}`, which decodes to a string of JSON and renders
     * as a brace of braces on the page.
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

        if ($map !== null) {
            $out = [];

            foreach (Locale::cases() as $locale) {
                if (isset($map[$locale->value]) && trim((string) $map[$locale->value]) !== '') {
                    $out[$locale->value] = (string) $map[$locale->value];
                }
            }

            return [$key => $out === [] ? null : TranslationPayload::encode($out)];
        }

        // Plain string: fill only the active locale and keep the others.
        $existing = TranslationPayload::toArray($attributes[$key] ?? null);
        $existing[App::getLocale()] = (string) $value;

        return [$key => TranslationPayload::encode($existing)];
    }
}
