{{--
    The geometric divider that sits between two bands.

    A run of the brand's four-module pattern unit, repeated along the inline
    axis. The unit is a single motif and deliberately *not* a seamless fill —
    config/brand.php documents why, and that constraint is the whole point of
    this band: repeating a motif that was drawn not to tile reads as a defect
    anywhere else, and here it reads as a row of emblems.

    The artwork URL arrives as `--h-pattern`, a single custom property printed
    once by the layout from config(). Keeping it a variable is what lets this
    file, and every dark band below it, stay free of inline styles while still
    resolving the image from configuration.

    Purely decorative, so it carries no text and is hidden from assistive
    technology rather than announced as an empty region.
--}}
@props(['tone' => 'light'])

<div {{ $attributes->class(['h-pattern', 'h-pattern--'.$tone]) }} aria-hidden="true"></div>