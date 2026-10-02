{{--
    The closing call to action.

    Rendered at the foot of the pages where the next step is obvious — speakers,
    programme, venue, archive — so a visitor who scrolls to the end is always
    offered something rather than a dead end. The buttons are driven by props
    rather than hardcoded to registration, because on the archive page the
    sensible next step is the current edition's programme, not its ticket page.

    The aurora is at half speed: this band is a panel, not a page opener, and it
    should not out-move the hero it is closing.
--}}
@props([
    'title',
    'text' => null,
    'primaryLabel' => null,
    'primaryUrl' => null,
    'secondaryLabel' => null,
    'secondaryUrl' => null,
])

<section class="ux-section py-5">
    <div class="container">
        <div class="ux-cta ux-reveal ux-reveal-scale">

            <x-aurora :speed="0.5" />

            <div class="position-relative">
                <h2 class="ux-cta__title">{{ $title }}</h2>

                @if ($text)
                    <p class="ux-cta__text">{{ $text }}</p>
                @endif

                @if ($primaryUrl && $primaryLabel)
                    <div class="ux-cta__actions">
                        <a href="{{ $primaryUrl }}"
                           class="ux-btn ux-btn--primary"
                           data-ux-magnetic="0.22">
                            <span>{{ $primaryLabel }}</span>
                        </a>

                        @if ($secondaryUrl && $secondaryLabel)
                            <a href="{{ $secondaryUrl }}"
                               class="ux-btn ux-btn--on-dark"
                               data-ux-magnetic="0.18">
                                <span>{{ $secondaryLabel }}</span>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>