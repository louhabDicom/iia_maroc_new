{{--
    The inner page hero, on the design system.

    `x-page-hero` is the 2024 band and it stays exactly as it is: it is the top of
    the pricing, archive, cart, checkout, order and unavailable pages too, and
    restyling it would change six pages the brief does not cover. This component
    is the same idea built from the `d-*` primitives, used only by the four pages
    being redesigned.

    What it carries, and why:

      * `facts` answers "when and where is it" above the fold. That is the first
        question a delegate arrives with, and answering it here is worth more than
        a paragraph of description.

      * `lede` is one short paragraph. Anything longer belongs in the body.

      * `image` is a real photograph from `assets/images/bg/` behind a scrim,
        because the reference design leads every section with an image and a
        photograph carries more authority than a flat colour band.

    No action row is rendered when there is no action, rather than rendering an
    empty flex container that still occupies margin.
--}}
@props([
    'title',
    'eyebrow' => null,
    'lede' => null,
    'image' => 'assets/images/bg/about_page_bg.jpg',
    'crumbs' => [],
    'facts' => [],
    'ctaLabel' => null,
    'ctaUrl' => null,
    'ctaUrlExternal' => false,
    'secondaryLabel' => null,
    'secondaryUrl' => null,
])

<section class="d-page-hero" style="background-image: url('{{ asset($image) }}');">

    {{-- Slower than the home hero (0.7): this band is shorter, so the same drift
         would carry a field further across it than the composition wants. --}}
    <x-aurora :speed="0.7" />

    <div class="container d-page-hero__inner">

        @if ($eyebrow)
            <p class="d-page-hero__eyebrow">{{ $eyebrow }}</p>
        @endif

        <h1 class="d-page-hero__title">{{ $title }}</h1>

        @if ($lede)
            <p class="d-page-hero__lede">{{ $lede }}</p>
        @endif

        @if ($facts !== [])
            <ul class="d-page-hero__facts">
                @foreach ($facts as $fact)
                    <li>
                        <i class="fas {{ $fact['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $fact['label'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($ctaUrl && $ctaLabel)
            <div class="d-page-hero__actions">
                <a href="{{ $ctaUrl }}"
                   class="d-btn d-btn--accent"
                   @if ($ctaUrlExternal) rel="noopener noreferrer nofollow" target="_blank" @endif>
                    <span>{{ $ctaLabel }}</span>
                </a>

                @if ($secondaryUrl && $secondaryLabel)
                    <a href="{{ $secondaryUrl }}" class="d-btn d-btn--on-dark">
                        <span>{{ $secondaryLabel }}</span>
                    </a>
                @endif
            </div>
        @endif

        {{-- Rendered only when the caller passed crumbs, so a page with no
             breadcrumb does not get an empty nav with a heading on it. --}}
        @if ($crumbs !== [])
            <nav class="d-page-hero__crumbs" aria-label="{{ __('nav.menu') }}">
                <ol>
                    <li>
                        <a href="{{ route('home') }}">@lang('nav.home')</a>
                    </li>
                    @foreach ($crumbs as $label => $url)
                        <li @if ($loop->last) aria-current="page" @endif>
                            @if ($loop->last || ! $url)
                                <span>{{ $label }}</span>
                            @else
                                <a href="{{ $url }}">{{ $label }}</a>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif
    </div>
</section>
