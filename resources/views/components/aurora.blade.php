{{--
    The drifting colour fields behind a hero.

    Extracted from the home page so every hero on the site — the inner-page bands
    included — is the same component rather than a copy that drifts.

    Purely decorative and marked `aria-hidden`: a screen reader announcing "group
    of decorative images" on every page is noise, and nothing here carries
    information the text beside it does not.

    The `speed` prop scales the parallax factor ux.js reads from
    `data-ux-parallax`. Higher moves more. The gold field is the slowest because
    it is the largest and lowest in the band, and a fast large field reads as the
    page sliding rather than as depth.

    `grain` exists for a reason beyond taste: the film-grain overlay is what
    stops these large gradients from banding into visible steps on an 8-bit
    panel. It is left on everywhere except the very largest band, where the
    decode cost is not worth a texture nobody will see.
--}}
@props([
    'speed' => 1.0,
    'grain' => true,
])

<div class="ux-aurora" aria-hidden="true">
    <div class="ux-aurora__field ux-aurora__field--magenta"
         data-ux-parallax="{{ round(0.10 * $speed, 3) }}"></div>

    <div class="ux-aurora__field ux-aurora__field--violet"
         data-ux-parallax="{{ round(0.16 * $speed, 3) }}"></div>

    <div class="ux-aurora__field ux-aurora__field--gold"
         data-ux-parallax="{{ round(0.07 * $speed, 3) }}"></div>

    @if ($grain)
        <div class="ux-aurora__grain"></div>
    @endif
</div>