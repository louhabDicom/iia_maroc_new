{{--
    Inner page hero.

    This is the band at the top of every page except the home page, and it is the
    single most reused component on the site. It carries the same aurora, the same
    eyebrow treatment and the same type scale as the home hero, so moving between
    pages keeps one visual key rather than feeling like a different website.

    Three things are worth knowing about the props:

      * `facts` answers "when and where is it" without scrolling. That is the
        first question every delegate arrives with, and answering it above the
        fold is worth more than any paragraph of description. It is an array of
        `['icon' => 'fa-calendar', 'label' => '…']` so the caller supplies text
        already translated.

      * `lede` is a short paragraph, not a description. Anything longer belongs in
        the body; the band is for orientation.

      * `ctaLabel`/`ctaUrl` produce the primary action in the band. Omit both and
        no action row is rendered at all, rather than rendering an empty flex
        container that still occupies margin.
--}}
@props([
    'title',
    'subTitle' => null,
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

<section class="page-hero" style="background-image: url('{{ asset($image) }}');">

    {{-- Slower than the home hero (0.7): this band is shorter, so the same
         drift would carry a field further across it than the composition wants. --}}
    <x-aurora :speed="0.7" />

    <div class="container">
        <div class="section-title">

            @if ($eyebrow)
                {{-- The hero eyebrow has a live pulsing dot, which is a "this is
                     the current edition" signal. It is right at the top of the
                     page and wrong halfway down it, so it is not reused here. --}}
                <p class="ux-hero__eyebrow">{{ $eyebrow }}</p>
            @endif

            @if ($subTitle)
                <h5 class="sub-title white-2">{{ $subTitle }}</h5>
            @endif

            <h1 class="title white">{{ $title }}</h1>

            @if ($lede)
                <p class="page-hero__lede">{{ $lede }}</p>
            @endif
        </div>

        @if ($facts !== [])
            <ul class="page-hero__facts">
                @foreach ($facts as $fact)
                    <li>
                        <i class="fas {{ $fact['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $fact['label'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($ctaUrl && $ctaLabel)
            <div class="ux-hero__actions">
                <a href="{{ $ctaUrl }}"
                   class="ux-btn ux-btn--primary"
                   data-ux-magnetic="0.2">
                    <span>{{ $ctaLabel }}</span>
                </a>

                @if ($secondaryUrl && $secondaryLabel)
                    <a href="{{ $secondaryUrl }}"
                       class="ux-btn ux-btn--on-dark"
                       @if ($ctaUrlExternal) rel="noopener noreferrer nofollow" target="_blank" @endif
                       data-ux-magnetic="0.16">
                        <span>{{ $secondaryLabel }}</span>
                    </a>
                @endif
            </div>
        @endif

        {{-- Rendered only when the caller passed crumbs, so a page with no
             breadcrumb does not get an empty nav with a heading on it. --}}
        @if ($crumbs !== [])
            <nav class="breadcrumb-area" aria-label="{{ __('nav.menu') }}">
                <ol class="breadcrumb">
                    <li><a href="{{ route('home') }}">@lang('nav.home')</a></li>
                    @foreach ($crumbs as $label => $url)
                        <li @if ($loop->last) aria-current="page" @endif>
                            @if ($loop->last || ! $url)
                                {{ $label }}
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
