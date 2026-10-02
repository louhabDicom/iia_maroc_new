<?php

namespace App\Support;

/**
 * Brand asset helper.
 *
 * Resolves the artwork from the 2026 guidelines for the current locale, and
 * carries each file's intrinsic size alongside its path so the markup can
 * reserve the correct box before the image loads.
 *
 * Two traps this exists to avoid:
 *
 *  - The Arabic lockup is the main version and is roughly three times as wide
 *    as the English and French pair, because it carries the dates and the
 *    location. Sizing a header logo once and reusing it everywhere stretches
 *    two locales or crops one, so the dimensions are resolved per locale.
 *
 *  - The pattern tiles are not seamless (about 161.9pt of artwork on a 177.1pt
 *    pitch). `pattern()` deliberately returns a single motif rather than a
 *    tileable background, because repeating one shows the gaps as a grid of
 *    holes.
 */
final class Brand
{
    /**
     * Logo for a locale, as [path, width, height].
     *
     * An unmapped locale falls back through the application's fallback locale
     * and then to French, so the header always has artwork rather than an
     * <img> with an empty src.
     *
     * @return array{path: string, width: int, height: int}
     */
    public static function logo(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        /** @var array<string, array{path: string, width: int, height: int}> $logos */
        $logos = (array) config('brand.logos', []);

        foreach ([$locale, config('app.fallback_locale'), config('brand.logo_fallback_locale')] as $candidate) {
            if (is_string($candidate) && isset($logos[$candidate])) {
                return $logos[$candidate];
            }
        }

        // Config has been emptied or corrupted. Returning the French lockup
        // keeps the page rendering; a thrown exception here would take out the
        // header of every page on the site.
        return [
            'path' => 'assets/brand/logo-french.png',
            'width' => 360,
            'height' => 205,
        ];
    }

    /** Public URL of the locale's logo. */
    public static function logoUrl(?string $locale = null): string
    {
        return asset(self::logo($locale)['path']);
    }

    /** Public URL of the standalone emblem. */
    public static function emblemUrl(): string
    {
        return asset((string) config('brand.emblem'));
    }

    /**
     * Public URL of a pattern motif: tile "a", "b" or "c", or the four-module
     * unit when "modules" is passed.
     */
    public static function pattern(string $key = 'a'): string
    {
        if ($key === 'modules') {
            return asset((string) config('brand.pattern_modules'));
        }

        /** @var array<string, string> $tiles */
        $tiles = (array) config('brand.pattern_tiles', []);

        return asset($tiles[$key] ?? $tiles['a'] ?? '');
    }
}
