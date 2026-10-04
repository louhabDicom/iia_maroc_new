{{--
    The closing call to action, on the design system.

    `x-cta-band` is shared with the pages outside this brief, so it stays. Same
    contract, `.d-cta` markup.

    The band is the one place on the site where the warm accent is allowed to be
    a filled button: it is the single most important action of the page, and two
    primary buttons side by side would compete instead of reading as one decision.
--}}
@props([
    'title',
    'text' => null,
    'primaryLabel' => null,
    'primaryUrl' => null,
    'secondaryLabel' => null,
    'secondaryUrl' => null,
])

<section class="d-section d-cta">
    <div class="container">
        <div class="d-cta__inner">

            <div class="d-cta__body">
                <h2 class="d-cta__title">{{ $title }}</h2>

                @if ($text)
                    <p class="d-cta__text">{{ $text }}</p>
                @endif
            </div>

            @if (($primaryUrl && $primaryLabel) || ($secondaryUrl && $secondaryLabel))
                <div class="d-cta__actions">
                    @if ($primaryUrl && $primaryLabel)
                        <a href="{{ $primaryUrl }}" class="d-btn d-btn--accent">
                            <span>{{ $primaryLabel }}</span>
                        </a>
                    @endif

                    @if ($secondaryUrl && $secondaryLabel)
                        <a href="{{ $secondaryUrl }}" class="d-btn d-btn--on-dark">
                            <span>{{ $secondaryLabel }}</span>
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</section>
