<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Logos
    |--------------------------------------------------------------------------
    |
    | Extracted from the 2026 brand guidelines. The Arabic lockup is the main
    | version and is markedly wider than the Latin ones because it carries the
    | dates and the location; English and French are the second and third
    | versions of the same lockup. Each language therefore gets its own correct
    | artwork rather than the Arabic file being reused everywhere.
    |
    | Intrinsic dimensions travel with the path so the markup can declare
    | width/height and reserve the right box before the PNG arrives. The Arabic
    | lockup is roughly three times as wide as the Latin pair, so a single
    | hard-coded size would be wrong for two of the three locales.
    |
    */

    'logos' => [
        'ar' => ['path' => 'assets/brand/logo-arabic-main.png', 'width' => 800, 'height' => 251],
        'en' => ['path' => 'assets/brand/logo-english.png', 'width' => 360, 'height' => 201],
        'fr' => ['path' => 'assets/brand/logo-french.png', 'width' => 360, 'height' => 205],
    ],

    /*
    | Any locale without its own lockup falls back to the application's
    | fallback locale, then to French, which is the site default. The fallback
    | chain means an unmapped locale still renders a logo instead of an
    | <img> with an empty src.
    */

    'logo_fallback_locale' => 'fr',

    /*
    | Standalone mark, for the places a lockup will not fit: the footer, the
    | compact navigation bar, and the favicon.
    */

    'emblem' => 'assets/brand/logo-emblem.png',

    /*
    |--------------------------------------------------------------------------
    | Pattern
    |--------------------------------------------------------------------------
    |
    | The brand tiles are NOT a seamless fill. The source artwork measures about
    | 161.9pt across on a 177.1pt pitch, which leaves roughly 15pt of blank
    | space between repeats; tiling them as a CSS background shows those gaps
    | as a regular grid of holes and reads as a mistake.
    |
    | They are used instead as single decorative motifs, positioned once.
    |
    */

    'pattern_tiles' => [
        'a' => 'assets/brand/pattern-tile-a.png',
        'b' => 'assets/brand/pattern-tile-b.png',
        'c' => 'assets/brand/pattern-tile-c.png',
    ],

    'pattern_modules' => 'assets/brand/pattern-modules.png',

    /*
    |--------------------------------------------------------------------------
    | Landing page imagery
    |--------------------------------------------------------------------------
    |
    | Placeholders — and the single place the real photographs get swapped in.
    | `src` is a path under `public/`, and each entry travels with the file's
    | intrinsic `width`/`height` so the markup can reserve the correct box
    | before the bytes arrive. A collage that reflows as its photographs land
    | is the most visible jank on the whole page.
    |
    | Swapping one in is two edits: drop the file in `public/assets/images/`
    | and point this entry at it. Nothing else in the templates names a path.
    |
    */

    'homepage' => [

        // The large photograph of the "why this conference" band: delegates
        // talking to each other on the exhibition floor.
        'about_main' => [
            'src' => 'assets/images/about_img1.png',
            'width' => 654,
            'height' => 546,
        ],

        // The smaller card that overlaps it: a plenary hall with the session
        // screen lit. It carries the ARABCIA caption strip, which is why it is
        // shot wide enough for the text to sit inside it.
        'about_inset' => [
            'src' => 'assets/images/about_img2.png',
            'width' => 360,
            'height' => 270,
        ],

        // Shown in the partners band when no sponsor row carries a logo yet.
        // One designed mark reads as a wall of one; an empty dark block reads
        // as a page that failed to load.
        'partners_fallback' => [
            'src' => 'assets/images/logo/cih-logo.png',
            'width' => 349,
            'height' => 800,
        ],
    ],

];
