@extends('layouts.app')

@section('title', __('sponsoring.title'))
@section('description', __('sponsoring.meta_description', ['year' => $edition->year]))

@section('content')

@php
$year = $edition->year;

// Closing band photograph (placeholder, swap the path here).
$closingImage = ['src' => 'assets/images/sponsoring/ban3.png', 'width' => 613, 'height' => 408];

$reasons = [
    ['icon' => 'fa-bullseye', 'title' => __('sponsoring.why.reach.title'), 'text' => __('sponsoring.why.reach.text')],
    ['icon' => 'fa-user-tie', 'title' => __('sponsoring.why.profiles.title'), 'text' => __('sponsoring.why.profiles.text')],
    ['icon' => 'fa-handshake', 'title' => __('sponsoring.why.decision.title'), 'text' => __('sponsoring.why.decision.text')],
    ['icon' => 'fa-bullhorn', 'title' => __('sponsoring.why.visibility.title'), 'text' => __('sponsoring.why.visibility.text')],
    ['icon' => 'fa-people-group', 'title' => __('sponsoring.why.community.title'), 'text' => __('sponsoring.why.community.text')],
];

// The four sponsorship formulas, drawn from one array so a fifth tier is a
// data change rather than a new markup block. The price is split from its
// unit so the amount can sit on its own line and be read as a figure.
$formulas = collect([
    ['key' => 'platinum', 'icon' => 'fa-gem', 'tone' => 'dark'],
    ['key' => 'gold', 'icon' => 'fa-award', 'tone' => 'gold'],
    ['key' => 'silver', 'icon' => 'fa-medal', 'tone' => 'silver'],
    ['key' => 'lab', 'icon' => 'fa-flask', 'tone' => 'lab'],
])->map(static fn (array $formula): array => [
    ...$formula,
    ...__('sponsoring.packages.'.$formula['key']),
]);

// À-la-carte activations: name, price, one or more lines. Kept as arrays
// rather than objects so the same list serves all three languages.
$activations = __('sponsoring.activations.items');


// "Download the dossier" card: the first word is printed on its own,
// lighter line, as in the design. Works in fr / en / ar.
$dossierTitle = __('sponsoring.dossier.title', ['year' => $year]);
$dossierFirst = \Illuminate\Support\Str::before($dossierTitle, ' ');
$dossierRest = \Illuminate\Support\Str::after($dossierTitle, ' ');

// Previous partners — ONLINE logos. Each logo is fetched from Google's
// favicon service by domain (placeholder quality). To use a real
// wordmark, add 'logo' => 'https://…/logo.png' to the entry and it wins.
// Rows: the first 4 entries form the centred top row, the rest the bottom row.
$favicon = fn (string $domain): string => 'https://t3.gstatic.com/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&size=256&url=https://' . $domain;
$allies = [
['name' => 'Banque Populaire', 'domain' => 'groupebcp.com'],
['name' => 'Al Omrane', 'domain' => 'alomrane.ma'],
['name' => 'Wolters Kluwer', 'domain' => 'wolterskluwer.com'],
['name' => 'Marsa Maroc', 'domain' => 'marsamaroc.co.ma'],
['name' => 'Mazars', 'domain' => 'mazars.com'],
['name' => 'Caseware', 'domain' => 'caseware.com'],
['name' => 'Mega', 'domain' => 'mega.ma'],
['name' => 'Finances', 'domain' => 'finances.ma'],
['name' => 'PRC', 'domain' => 'prc.ma'],
['name' => 'Medizine', 'domain' => 'medizine.ma'],
];
$allyRows = [array_slice($allies, 0, 4), array_slice($allies, 4)];
@endphp

{{-- The shared front-office hero, with the sponsors page's own title and
     pitch. Previously this page mounted `x-home.hero` verbatim, which put the
     organiser's name and the edition's theme in the `h1` and sent the reader
     back to the sponsors page from the sponsors page. --}}
<x-front.hero
    :title="__('sponsoring.hero.title_lead').' '.__('sponsoring.hero.title_accent')"
    :eyebrow="__('sponsoring.hero.eyebrow')"
    :lede="__('sponsoring.hero.lede', ['year' => $year])"
    :crumbs="[__('nav.sponsors') => null]"
    :facts="[
        ['icon' => 'fa-calendar-alt', 'label' => $edition->dateLine($locale)],
        ['icon' => 'fa-map-marker-alt',  'label' => $edition->venueLine($locale)],
    ]"
    :cta-label="__('sponsoring.hero_actions.download')"
    :cta-url="$dossier ? $dossier->downloadUrl() : route('contact')"
    :secondary-label="__('sponsoring.hero_actions.partner')"
    :secondary-url="route('contact')"
    image="assets/images/bg/hero_bg1.jpg" />

<div class="sp-page">

    {{-- Band 1 — the pitch --}}
    <section class="sp-intro" aria-labelledby="sponsoring-intro-title">
        <div class="container">
            <div class="sp-intro__grid">
                <div class="sp-intro__body">
                    <p class="sp-eyebrow">@lang('sponsoring.hero.eyebrow')</p>
                    <h2 id="sponsoring-intro-title" class="sp-intro__title">
                        <span>@lang('sponsoring.hero.title_lead')</span>
                        <span class="sp-intro__title-accent">@lang('sponsoring.hero.title_accent')</span>
                    </h2>
                    <p class="sp-lede">@lang('sponsoring.hero.lede', ['year' => $year])</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Band 2 — why --}}
    <section class="sp-why" aria-labelledby="sponsoring-why-title">
        <div class="container">
            <div class="sp-why__grid">
                <div class="sp-why__body">
                    <p class="sp-eyebrow">@lang('sponsoring.why.eyebrow')</p>
                    <h2 id="sponsoring-why-title" class="sp-title">@lang('sponsoring.why.title')</h2>
                    <p class="sp-lede">@lang('sponsoring.why.lede')</p>
                </div>

                <ul class="sp-reasons" data-ux-stagger="90">
                    @foreach ($reasons as $reason)
                    <li class="sp-reason ux-reveal">
                        <span class="sp-reason__icon" aria-hidden="true"><i class="fas {{ $reason['icon'] }}"></i></span>
                        <h3 class="sp-reason__title">{{ $reason['title'] }}</h3>
                        <p class="sp-reason__text">{{ $reason['text'] }}</p>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- Band 2.5 — the four sponsorship formulas. This is the commercial core
             of the page: price, scarcity and inclusions, in that order, so a
             sponsor can compare without opening the dossier. --}}
    <section class="sp-packages" aria-labelledby="sponsoring-packages-title">
        <div class="container">
            <div class="sp-head sp-head--center">
                <p class="sp-eyebrow sp-eyebrow--center">@lang('sponsoring.packages.eyebrow')</p>
                <h2 id="sponsoring-packages-title" class="sp-title">@lang('sponsoring.packages.title')</h2>
            </div>

            <div class="sp-package-grid" data-ux-stagger="90">
                @foreach ($formulas as $formula)
                <article class="sp-package sp-package--{{ $formula['tone'] }} ux-reveal">
                    <header class="sp-package__head">
                        <span class="sp-package__icon" aria-hidden="true"><i class="fas {{ $formula['icon'] }}"></i></span>
                        <h3 class="sp-package__name">{{ $formula['name'] }}</h3>
                    </header>

                    <p class="sp-package__price">
                        <span class="sp-package__amount" dir="ltr">{{ $formula['price'] }}</span>
                        <span class="sp-package__unit">@lang('sponsoring.packages.per')</span>
                    </p>

                    <p class="sp-package__limit">{{ $formula['limit'] }}</p>

                    <p class="sp-package__summary">{{ $formula['summary'] }}</p>

                    <ul class="sp-package__features">
                        @foreach ($formula['features'] as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>

                    <a href="{{ route('contact') }}" class="sp-package__cta">
                        <span>{{ $formula['cta'] }}</span>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </article>
                @endforeach
            </div>

            <div class="sp-packages__foot">
                <a href="{{ route('contact') }}" class="sp-btn-outline">
                    <span>@lang('sponsoring.packages.compare')</span>
                    <i class="fas fa-columns" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </section>

    {{-- Band 2.6 — à-la-carte activations. Six tiles rather than a table: the
             entries are short and independent, and a visitor picking one is
             scanning for a name, not comparing columns. --}}
    <section class="sp-activations" aria-labelledby="sponsoring-activations-title">
        <div class="container">
            <div class="sp-head sp-head--center">
                <p class="sp-eyebrow sp-eyebrow--center">@lang('sponsoring.activations.eyebrow')</p>
                <h2 id="sponsoring-activations-title" class="sp-title">@lang('sponsoring.activations.title')</h2>
            </div>

            <div class="sp-activation-grid" data-ux-stagger="70">
                @foreach ($activations as $activation)
                <article class="sp-activation ux-reveal">
                    <div class="sp-activation__top">
                        <h3 class="sp-activation__name">{{ $activation['name'] }}</h3>
                        <p class="sp-activation__price" dir="ltr">{{ $activation['price'] }}</p>
                    </div>

                    <ul class="sp-activation__features">
                        @foreach ($activation['features'] as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>
                </article>
                @endforeach
            </div>

            <div class="sp-bespoke">
                <span class="sp-bespoke__icon" aria-hidden="true"><i class="fas fa-pen-ruler"></i></span>
                <div class="sp-bespoke__body">
                    <h3 class="sp-bespoke__title">@lang('sponsoring.bespoke.title')</h3>
                    <p class="sp-bespoke__text">@lang('sponsoring.bespoke.text')</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Band 3 — the ask: indigo panel + dossier card --}}
    <section class="sp-cta-pair" aria-labelledby="sponsoring-build-title">
        <div class="container">
            <div class="sp-cta-pair__grid">

                <div class="sp-cta-panel ux-reveal">
                    <div class="sp-cta-panel__inner">
                        <p class="sp-eyebrow sp-eyebrow--on-dark">@lang('sponsoring.build.eyebrow')</p>
                        <h2 id="sponsoring-build-title" class="sp-cta-panel__title">@lang('sponsoring.build.title')</h2>
                        <p class="sp-cta-panel__text">@lang('sponsoring.build.lede', ['year' => $year])</p>

                        <a href="{{ route('contact') }}" class="sp-btn-light">
                            <span>@lang('sponsoring.build.cta')</span>
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>

                <div class="sp-dossier ux-reveal">
                    <div class="sp-dossier__head">
                        <span class="sp-dossier__icon" aria-hidden="true"><i class="far fa-file-alt"></i></span>

                        <h3 class="sp-dossier__title">
                            <span class="sp-dossier__lead">{{ $dossierFirst }}</span>
                            {{ $dossierRest }}
                        </h3>

                        {{-- Always drawn, as in the design. With no published
                                 dossier it leads to the contact page instead. --}}
                        <a href="{{ $dossier ? $dossier->downloadUrl() : route('contact') }}" class="sp-dossier__download">
                            <i class="fas fa-download" aria-hidden="true"></i>
                            <span class="visually-hidden">@lang($dossier ? 'sponsoring.dossier.cta' : 'sponsoring.build.cta')</span>
                        </a>
                    </div>

                    <p class="sp-dossier__text">@lang('sponsoring.dossier.lede')</p>
                </div>

            </div>
        </div>
    </section>

    {{-- Band 4 — this edition's sponsors --}}
    <section class="sp-roster" aria-labelledby="sponsoring-roster-title">
        <div class="container">
            <div class="sp-head">
                <p class="sp-eyebrow">@lang('sponsoring.roster.eyebrow', ['year' => $year])</p>
                <h2 id="sponsoring-roster-title" class="sp-title">@lang('sponsoring.roster.title', ['year' => $year])</h2>
                <p class="sp-lede">@lang('sponsoring.roster.lede')</p>
            </div>

            <ul class="sp-wall" data-ux-stagger="70">
                @foreach ($sponsors as $sponsor)
                <li class="sp-plate">
                    @if ($sponsor->website_url)
                    <a href="{{ $sponsor->website_url }}" rel="noopener noreferrer sponsored" target="_blank">
                        <x-sponsor-logo :sponsor="$sponsor" />
                    </a>
                    @else
                    <x-sponsor-logo :sponsor="$sponsor" />
                    @endif
                </li>
                @endforeach

                @foreach ($pendingSlots as $slot)
                <li class="sp-plate sp-plate--pending" aria-hidden="true">
                    <i class="fas fa-image"></i>
                    <span>@lang('sponsoring.roster.pending')</span>
                </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Band 5 — previous partners: two rows of online logos (4 + 6),
             the arrows rotate the logos through the two rows. --}}
    <section class="sp-allies" aria-labelledby="sponsoring-allies-title">
        <div class="container">
            <div class="sp-head">
                <p class="sp-eyebrow">@lang('sponsoring.allies.eyebrow')</p>
                <h2 id="sponsoring-allies-title" class="sp-title">@lang('sponsoring.allies.title')</h2>
                <p class="sp-lede">@lang('sponsoring.allies.lede')</p>
            </div>

            <div class="sp-allies__stage" data-allies>
                <button type="button" class="sp-arrow sp-arrow--edge sp-arrow--prev" data-allies-prev>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 5l-7 7 7 7"></path>
                    </svg>
                    <span class="visually-hidden">@lang('sponsoring.rail.previous')</span>
                </button>

                @foreach ($allyRows as $rowIndex => $row)
                <ul class="sp-allies__row {{ $rowIndex === 0 ? 'sp-allies__row--lead' : '' }}">
                    @foreach ($row as $ally)
                    <li class="sp-ally">
                        <a href="https://{{ $ally['domain'] }}" rel="noopener noreferrer sponsored" target="_blank">
                            <img src="{{ $ally['logo'] ?? $favicon($ally['domain']) }}"
                                alt="{{ $ally['name'] }}"
                                width="120" height="48"
                                loading="lazy" decoding="async" referrerpolicy="no-referrer">
                        </a>
                    </li>
                    @endforeach
                </ul>
                @endforeach

                <button type="button" class="sp-arrow sp-arrow--edge sp-arrow--next" data-allies-next>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 5l7 7-7 7"></path>
                    </svg>
                    <span class="visually-hidden">@lang('sponsoring.rail.next')</span>
                </button>
            </div>
        </div>
    </section>

    {{-- Band 6 — previous editions in pictures --}}
    <section class="sp-gallery" aria-labelledby="sponsoring-gallery-title">
        <div class="container">
            <div class="sp-head">
                <p class="sp-eyebrow">@lang('sponsoring.gallery.eyebrow')</p>
                <h2 id="sponsoring-gallery-title" class="sp-title">@lang('sponsoring.gallery.title')</h2>
            </div>

            @if ($gallery->isNotEmpty())
            <div class="sp-rail-wrap">
                <button type="button" class="sp-arrow sp-arrow--prev" data-rail-prev data-rail="gallery" hidden>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 5l-7 7 7 7"></path>
                    </svg>
                    <span class="visually-hidden">@lang('sponsoring.rail.previous')</span>
                </button>

                <ul class="sp-gallery__rail" data-rail-track="gallery">
                    @foreach ($gallery as $index => $photo)
                    <li>
                        <a href="{{ asset($photo['file']) }}"
                            data-lightbox="sponsoring-gallery"
                            data-caption="@lang('sponsoring.gallery.alt', ['number' => $index + 1])">
                            <img src="{{ asset($photo['file']) }}"
                                width="{{ $photo['width'] }}" height="{{ $photo['height'] }}"
                                alt="@lang('sponsoring.gallery.alt', ['number' => $index + 1])"
                                loading="lazy" decoding="async">
                        </a>
                    </li>
                    @endforeach
                </ul>

                <button type="button" class="sp-arrow sp-arrow--next" data-rail-next data-rail="gallery" hidden>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 5l7 7-7 7"></path>
                    </svg>
                    <span class="visually-hidden">@lang('sponsoring.rail.next')</span>
                </button>
            </div>
            @endif
        </div>
    </section>

    {{-- Closing band --}}
    <section class="sp-closing" aria-labelledby="sponsoring-closing-title">
        <img class="sp-closing__bg"
            src="{{ asset($closingImage['src']) }}"
            width="{{ $closingImage['width'] }}" height="{{ $closingImage['height'] }}"
            alt="" loading="lazy" decoding="async" aria-hidden="true">


        <div class="container sp-closing__inner">
            <div class="sp-closing__content">
                <h2 id="sponsoring-closing-title" class="sp-closing__title">
                    @lang('sponsoring.cta.title', ['year' => $year])
                </h2>
                <span class="sp-closing__line" aria-hidden="true"></span>
            </div>

            <a href="{{ route('contact') }}" class="sp-btn-light sp-closing__btn">
                <span>@lang('sponsoring.cta.button')</span>
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </section>
</div>

{{-- Styles live INSIDE the section on purpose: anything after @endsection
         in a child view is printed before <!DOCTYPE>, which forces quirks mode.
         Movable to public/assets/css/sponsoring.css once settled. --}}
<style>
    .sp-page {
        --sp-ink: #1b1464;
        --sp-brand: #4f3cc9;
        --sp-accent: #6d3bd6;
        --sp-muted: #5f6384;
        --sp-tint: #ece8ff;
        --sp-line: #ddd8f5;
        --sp-surface: #f6f4ff;
        --sp-deep: #2f1f9c;
        color: var(--sp-ink);
    }

    .sp-page section {
        position: relative;
        overflow: hidden;
    }

    .sp-page h2,
    .sp-page h3,
    .sp-page p,
    .sp-page ul {
        margin: 0;
    }

    .sp-page ul {
        list-style: none;
        padding: 0;
    }

    .sp-page img {
        max-width: 100%;
    }

    [dir="rtl"] .sp-page .fa-arrow-right,
    [dir="rtl"] .sp-arrow svg {
        transform: scaleX(-1);
    }

    /* ---------- Shared ---------- */
    .sp-eyebrow {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-block-end: 14px;
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: var(--sp-brand);
    }

    .sp-eyebrow::before {
        content: "";
        width: 44px;
        height: 1px;
        background: currentColor;
        opacity: .45;
    }

    [dir="rtl"] .sp-eyebrow {
        letter-spacing: 0;
    }

    .sp-title {
        margin-block-end: 16px;
        font-size: clamp(1.6rem, 2.6vw, 2.15rem);
        font-weight: 700;
        line-height: 1.2;
        color: var(--sp-ink);
    }

    .sp-lede {
        max-width: 46ch;
        font-size: .95rem;
        line-height: 1.75;
        color: var(--sp-muted);
    }

    .sp-head {
        margin-block-end: 40px;
    }

    /* ---------- Band 1 ---------- */
    .sp-intro {
        padding-block: 76px 64px;
        background: url('assets/images/sponsoring/ban.png') center / cover no-repeat;
       
    }

    .sp-intro__grid {
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr);
        gap: 56px;
        align-items: center;
    }

    .sp-intro__title {
        max-width: 14em;
        margin-block-end: 26px;
        font-size: clamp(2rem, 3.6vw, 2.9rem);
        font-weight: 700;
        line-height: 1.15;
        color: var(--sp-ink);
    }

    .sp-intro__title span {
        display: block;
    }

    .sp-intro__title-accent {
        color: var(--sp-accent);
    }

    /* ---------- Band 2 ---------- */
    .sp-why {
        padding-block: 72px;
        background: url('assets/images/sponsoring/ban1.png') center / cover no-repeat;
    }

    .sp-why__grid {
        display: grid;
        grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr);
        gap: 56px;
        align-items: center;
    }

    .sp-why__body .sp-lede {
        max-width: 42ch;
    }

    .sp-reasons {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 20px;
    }

    .sp-reason {
        padding: 26px 22px;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 18px 40px -26px rgba(60, 40, 160, .4);
    }

    .sp-reason__icon {
        display: grid;
        place-items: center;
        width: 48px;
        height: 48px;
        margin-block-end: 16px;
        border-radius: 14px;
        background: var(--sp-tint);
        color: var(--sp-brand);
        font-size: 1.15rem;
    }

    .sp-reason__title {
        margin-block-end: 8px;
        font-size: 1rem;
        font-weight: 700;
        color: var(--sp-brand);
    }

    .sp-reason__text {
        font-size: .8rem;
        line-height: 1.6;
        color: var(--sp-muted);
    }

    /* ---------- Centered section heads ---------- */
    .sp-head--center {
        text-align: center;
    }

    .sp-eyebrow--center {
        justify-content: center;
    }

    .sp-head--center .sp-lede {
        margin-inline: auto;
    }

    /* ---------- Band 2.5 — the four formulas ---------- */
    .sp-packages {
        padding-block: 76px 64px;
        background: var(--sp-surface);
    }

    .sp-package-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 22px;
        align-items: start;
    }

    .sp-package {
        display: flex;
        flex-direction: column;
        padding: 30px 26px;
        border-radius: 20px;
        background: #fff;
        border: 1px solid var(--sp-line);
        box-shadow: 0 20px 44px -30px rgba(60, 40, 160, .5);
    }

    /* The top tier reads as the default choice: darker frame and a lift. */
    .sp-package--dark {
        border-color: transparent;
        background: linear-gradient(160deg, #2b1d9a 0%, #3a27b3 100%);
        color: #fff;
        transform: translateY(-10px);
        box-shadow: 0 30px 60px -34px rgba(43, 29, 154, .85);
    }

    .sp-package__head {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-block-end: 20px;
    }

    .sp-package__icon {
        display: grid;
        place-items: center;
        width: 44px;
        height: 44px;
        border-radius: 13px;
        background: var(--sp-tint);
        color: var(--sp-brand);
        font-size: 1.05rem;
        flex: 0 0 auto;
    }

    .sp-package__name {
        font-size: .95rem;
        font-weight: 700;
        letter-spacing: .02em;
        color: var(--sp-ink);
    }

    .sp-package__price {
        display: flex;
        align-items: baseline;
        flex-wrap: wrap;
        gap: 8px;
        padding-block-end: 14px;
        border-block-end: 1px solid var(--sp-line);
    }

    .sp-package__amount {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.1;
        color: var(--sp-accent);
    }

    .sp-package__unit {
        font-size: .72rem;
        font-weight: 600;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--sp-muted);
    }

    .sp-package__limit {
        margin-block-start: 12px;
        font-size: .74rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--sp-brand);
    }

    .sp-package__summary {
        margin-block-start: 12px;
        margin-block-end: 18px;
        font-size: .84rem;
        line-height: 1.65;
        color: var(--sp-muted);
    }

    .sp-package__features {
        display: grid;
        gap: 9px;
        margin-block-end: 24px;
    }

    .sp-package__features li {
        position: relative;
        padding-inline-start: 22px;
        font-size: .82rem;
        line-height: 1.5;
        color: var(--sp-ink);
    }

    .sp-package__features li::before {
        content: "\f00c";
        position: absolute;
        inset-inline-start: 0;
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        font-size: .7rem;
        color: var(--sp-brand);
    }

    .sp-package__cta {
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-block-start: auto;
        padding: 14px 22px;
        border-radius: 999px;
        background: var(--sp-deep);
        font-size: .76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .02em;
        color: #fff;
        text-decoration: none;
        transition: transform .2s, box-shadow .2s;
    }

    .sp-package__cta:hover,
    .sp-package__cta:focus-visible {
        transform: translateY(-2px);
        box-shadow: 0 14px 28px -16px rgba(43, 29, 154, .8);
        color: #fff;
    }

    .sp-package--dark .sp-package__name,
    .sp-package--dark .sp-package__features li {
        color: #fff;
    }

    .sp-package--dark .sp-package__price {
        border-block-end-color: rgba(255, 255, 255, .25);
    }

    .sp-package--dark .sp-package__amount,
    .sp-package--dark .sp-package__limit {
        color: #d9d2ff;
    }

    .sp-package--dark .sp-package__unit,
    .sp-package--dark .sp-package__summary {
        color: rgba(255, 255, 255, .75);
    }

    .sp-package--dark .sp-package__features li::before {
        color: #fff;
    }

    .sp-package--dark .sp-package__cta {
        background: #fff;
        color: var(--sp-deep);
    }

    .sp-package--dark .sp-package__cta:hover,
    .sp-package--dark .sp-package__cta:focus-visible {
        color: var(--sp-deep);
    }

    .sp-packages__foot {
        display: flex;
        justify-content: center;
        margin-block-start: 40px;
    }

    .sp-btn-outline {
        display: inline-flex;
        align-items: center;
        gap: 14px;
        padding: 15px 32px;
        border: 1.5px solid var(--sp-brand);
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: var(--sp-brand);
        text-decoration: none;
        transition: background .2s, color .2s;
    }

    .sp-btn-outline:hover,
    .sp-btn-outline:focus-visible {
        background: var(--sp-brand);
        color: #fff;
    }

    /* ---------- Band 2.6 — activations ---------- */
    .sp-activations {
        padding-block: 72px;
        background: #fff;
    }

    .sp-activation-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 20px;
    }

    .sp-activation {
        display: flex;
        flex-direction: column;
        padding: 24px 22px;
        border-radius: 16px;
        background: var(--sp-surface);
        border: 1px solid var(--sp-line);
    }

    .sp-activation__top {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        justify-content: space-between;
        gap: 8px 16px;
        margin-block-end: 14px;
    }

    .sp-activation__name {
        font-size: .88rem;
        font-weight: 700;
        letter-spacing: .02em;
        color: var(--sp-ink);
    }

    .sp-activation__price {
        font-size: .82rem;
        font-weight: 700;
        color: var(--sp-accent);
        white-space: nowrap;
    }

    .sp-activation__features {
        display: grid;
        gap: 7px;
    }

    .sp-activation__features li {
        position: relative;
        padding-inline-start: 18px;
        font-size: .79rem;
        line-height: 1.55;
        color: var(--sp-muted);
    }

    .sp-activation__features li::before {
        content: "";
        position: absolute;
        inset-inline-start: 0;
        inset-block-start: .55em;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--sp-brand);
    }

    .sp-bespoke {
        display: flex;
        gap: 20px;
        align-items: flex-start;
        margin-block-start: 30px;
        padding: 28px 30px;
        border-radius: 18px;
        background: var(--sp-tint);
        border: 1px dashed #c3b8f0;
    }

    .sp-bespoke__icon {
        display: grid;
        place-items: center;
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: #fff;
        color: var(--sp-brand);
        font-size: 1.15rem;
        flex: 0 0 auto;
    }

    .sp-bespoke__title {
        margin-block-end: 8px;
        font-size: 1rem;
        font-weight: 700;
        color: var(--sp-ink);
    }

    .sp-bespoke__text {
        max-width: 68ch;
        font-size: .86rem;
        line-height: 1.7;
        color: var(--sp-muted);
    }

    /* ---------- Band 3 — "Une collaboration sur mesure" ---------- */
    .sp-cta-pair {
        padding-block: 40px 56px;
        background: #fff;
    }

    .sp-cta-pair__grid {
        display: grid;
        grid-template-columns: minmax(0, 1.45fr) minmax(0, 1fr);
        gap: 19px;
        align-items: stretch;
    }

    .sp-cta-panel {
        position: relative;
        border-radius: 10px;
        overflow: hidden;
        background: linear-gradient(120deg, #2b1d9a 0%, #3a27b3 62%, #2d1f9e 100%);
        box-shadow: 0 26px 46px -30px rgba(43, 29, 154, .7);
    }

    /* faint zellige line-art in the top corner */
    .sp-cta-panel::after {
        content: "";
        position: absolute;
        inset-block-start: 0;
        inset-inline-end: 0;
        width: 260px;
        height: 220px;
        opacity: .16;
        pointer-events: none;
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='260' height='220'%3E%3Cdefs%3E%3Cpattern id='p' width='60' height='60' patternUnits='userSpaceOnUse'%3E%3Cg fill='none' stroke='%23fff' stroke-width='1'%3E%3Crect x='15' y='15' width='30' height='30'/%3E%3Crect x='15' y='15' width='30' height='30' transform='rotate(45 30 30)'/%3E%3Ccircle cx='30' cy='30' r='8'/%3E%3C/g%3E%3C/pattern%3E%3C/defs%3E%3Crect width='260' height='220' fill='url(%23p)'/%3E%3C/svg%3E");
        -webkit-mask-image: linear-gradient(to left, #000 20%, transparent);
        mask-image: linear-gradient(to left, #000 20%, transparent);
    }

    [dir="rtl"] .sp-cta-panel::after {
        transform: scaleX(-1);
    }

    .sp-cta-panel__inner {
        position: relative;
        z-index: 1;
        padding: 48px 44px 36px;
    }

    .sp-eyebrow--on-dark {
        gap: 18px;
        font-size: .85rem;
        font-weight: 500;
        letter-spacing: .03em;
        color: rgba(255, 255, 255, .88);
    }

    .sp-eyebrow--on-dark::before {
        width: 32px;
        opacity: .85;
    }

    .sp-cta-panel__title {
        margin-block-end: 16px;
        font-size: clamp(1.6rem, 2.7vw, 2.2rem);
        font-weight: 500;
        line-height: 1.2;
        color: #fff;
    }

    .sp-cta-panel__text {
        max-width: 35rem;
        margin-block-end: 26px;
        font-size: 1rem;
        line-height: 1.75;
        color: #fff;
    }

    .sp-btn-light {
        display: inline-flex;
        align-items: end;
        justify-content: space-between;
        gap: 40px;
        min-width: min(100%, 360px);
        padding: 16px 34px;
        border-radius: 999px;
        background: #fff;
        font-size: .82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .01em;
        color: #1b1464;
        text-decoration: none;
        transition: transform .2s, box-shadow .2s;
    }

    .sp-btn-light:hover,
    .sp-btn-light:focus-visible {
        transform: translateY(-2px);
        box-shadow: 0 12px 26px -14px rgba(0, 0, 0, .55);
        color: #1b1464;
    }

    .sp-dossier {
        display: flex;
        flex-direction: column;
        padding: 38px;
        border-radius: 10px;
        background: #f5f4fd;
        border: 1px solid #e8e5f7;
    }

    .sp-dossier__head {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 16px;
        align-items: start;
        margin-block-end: 16px;
    }

    .sp-dossier__icon {
        padding-block-start: 6px;
        font-size: 2rem;
        line-height: 1;
        color: var(--sp-deep);
    }

    .sp-dossier__title {
        padding-block-start: 4px;
        font-size: .95rem;
        font-weight: 700;
        line-height: 1.4;
        letter-spacing: .01em;
        text-transform: uppercase;
        color: #1b1464;
        text-wrap: balance;
    }

    .sp-dossier__lead {
        display: block;
        font-weight: 500;
        color: var(--sp-brand);
    }

    [dir="rtl"] .sp-dossier__title {
        letter-spacing: 0;
    }

    .sp-dossier__download {
        display: grid;
        place-items: center;
        width: clamp(56px, 5.6vw, 80px);
        aspect-ratio: 1;
        border-radius: 50%;
        background: var(--sp-deep);
        color: #fff;
        font-size: 1.3rem;
        text-decoration: none;
        box-shadow: 0 14px 26px -14px rgba(43, 29, 154, .8);
        transition: transform .2s;
    }

    .sp-dossier__download:hover,
    .sp-dossier__download:focus-visible {
        transform: translateY(-2px);
        color: #fff;
    }

    .sp-dossier__text {
        font-size: .95rem;
        line-height: 1.7;
        color: #6b6f92;
    }

    /* ---------- Band 4 ---------- */
    .sp-roster {
        padding-block: 68px;
        background: var(--sp-surface);
    }

    .sp-wall {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 22px;
    }

    .sp-plate {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 132px;
        padding: 22px;
        border: 1px solid var(--sp-line);
        border-radius: 16px;
        background: #fff;
        transition: box-shadow .25s linear, transform .25s linear;
    }

    .sp-plate:hover {
        box-shadow: 0 14px 30px -18px rgba(60, 40, 160, .45);
        transform: translateY(-3px);
    }

    .sp-plate img {
        max-height: 62px;
        max-width: 100%;
        object-fit: contain;
    }

    .sp-plate--pending {
        flex-direction: column;
        gap: 10px;
        border-style: dashed;
        border-color: #cfc9ec;
        background: var(--sp-surface);
        color: #9a97b8;
    }

    .sp-plate--pending i {
        font-size: 1.3rem;
    }

    .sp-plate--pending span {
        font-size: .74rem;
    }

    /* ---------- Band 5 — "Ils nous ont accompagnés" ---------- */
    .sp-allies {
        padding-block: 56px 64px;
        background: #fff;
    }

    .sp-allies .sp-head {
        margin-block-end: 34px;
    }

    .sp-allies__stage {
        position: relative;
        display: grid;
        gap: 12px;
    }

    .sp-allies__row {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 12px;
    }

    .sp-ally {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 96px;
        padding: 14px 20px;
        border: 1px solid #eeebfa;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 14px 28px -22px rgba(60, 40, 160, .4);
        transition: box-shadow .25s linear, transform .25s linear;
    }

    .sp-ally:hover {
        box-shadow: 0 16px 30px -18px rgba(60, 40, 160, .5);
        transform: translateY(-2px);
    }

    .sp-allies__row--lead .sp-ally {
        flex: 0 1 220px;
    }

    .sp-allies__row:not(.sp-allies__row--lead) .sp-ally {
        flex: 1 1 150px;
    }

    .sp-ally a {
        display: grid;
        place-items: center;
        width: 100%;
        height: 100%;
    }

    .sp-ally img {
        width: auto;
        max-width: 100%;
        height: auto;
        max-height: 52px;
        object-fit: contain;
    }

    /* ---------- Rails / arrows (gallery + allies) ---------- */
    .sp-rail-wrap {
        position: relative;
    }

    .sp-gallery__rail {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        scroll-behavior: smooth;
        padding-block: 6px 14px;
        scrollbar-width: thin;
    }

    .sp-gallery__rail>li {
        flex: 0 0 auto;
        scroll-snap-align: start;
        width: clamp(230px, 28vw, 330px);
    }

    .sp-arrow {
        position: absolute;
        inset-block-start: 50%;
        z-index: 2;
        display: grid;
        place-items: center;
        width: 46px;
        height: 46px;
        margin-block-start: -23px;
        border: 1px solid var(--sp-line);
        border-radius: 50%;
        background: #fff;
        color: var(--sp-brand);
        box-shadow: 0 10px 24px -14px rgba(60, 40, 160, .5);
        cursor: pointer;
        transition: background .2s, color .2s, opacity .2s;
    }

    .sp-arrow:hover {
        background: var(--sp-brand);
        color: #fff;
    }

    .sp-arrow svg {
        width: 20px;
        height: 20px;
    }

    .sp-arrow--prev {
        inset-inline-start: -14px;
    }

    .sp-arrow--next {
        inset-inline-end: -14px;
    }

    .sp-arrow:disabled {
        opacity: .35;
        cursor: default;
    }

    .sp-arrow:disabled:hover {
        background: #fff;
        color: var(--sp-brand);
    }

    /* ---------- Band 6 ---------- */
    .sp-gallery {
        padding-block: 68px;
        background: var(--sp-surface);
    }

    .sp-gallery__rail a {
        display: block;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 14px 30px -20px rgba(60, 40, 160, .5);
    }

    .sp-gallery__rail img {
        display: block;
        width: 100%;
        aspect-ratio: 3 / 2;
        object-fit: cover;
        transition: transform .4s ease-out;
    }

    .sp-gallery__rail a:hover img,
    .sp-gallery__rail a:focus-visible img {
        transform: scale(1.04);
    }


/* ---------- Closing banner ---------- */
.sp-closing {
    position: relative;
    display: flex;
    align-items: center;
    min-height: 150px;
    padding-block: 130px;
    text-align: start;
    color: #fff;
    isolation: isolate;
}

.sp-closing__bg {
    position: absolute;
    inset: 0;
    z-index: -2;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center 55%;
}

.sp-closing::after {
    content: "";
    position: absolute;
    inset: 0;
    z-index: -1;
    background: linear-gradient(
        90deg,
        rgba(29, 15, 125, 0.82) 0%,
        rgba(39, 24, 157, 0.76) 55%,
        rgba(25, 17, 112, 0.82) 100%
    );
}

.sp-closing__inner {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 32px;
}

.sp-closing__content {
    flex: 1 1 0;
    min-width: 0;
}

.sp-closing__title {
    max-width: 15em;
    margin: 0;
    color: #fff;
    font-size: clamp(1.25rem, 2.2vw, 1.75rem);
    font-weight: 700;
    line-height: 1.2;
}

.sp-closing__line {
    display: block;
    width: 64px;
    height: 2px;
    margin-top: 16px;
    background: #fff;
}

.sp-closing__btn {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: space-between;
    gap: 28px;
    min-width: 220px;
    padding: 12px 22px;
    font-size: 0.7rem;
    white-space: nowrap;
}

/* ---------- Responsive ---------- */
@media (max-width: 767.98px) {
    .sp-closing {
        min-height: 180px;
        padding-block: 30px;
    }

    .sp-closing__inner {
        flex-direction: column;
        align-items: flex-start;
        gap: 22px;
    }

    .sp-closing__title {
        font-size: 1.35rem;
    }

    .sp-closing__btn {
        min-width: 0;
        max-width: 100%;
        white-space: normal;
    }
}

    /* ---------- Responsive ---------- */
    @media (min-width: 1300px) {
        .sp-arrow--edge.sp-arrow--prev {
            inset-inline-start: -56px;
        }

        .sp-arrow--edge.sp-arrow--next {
            inset-inline-end: -56px;
        }
    }

    @media (max-width: 991.98px) {

        .sp-intro__grid,
        .sp-why__grid,
        .sp-cta-pair__grid {
            grid-template-columns: minmax(0, 1fr);
            gap: 28px;
        }

        .sp-package-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .sp-package--dark {
            transform: none;
        }

        .sp-cta-panel__inner,
        .sp-dossier {
            padding: 32px 26px;
        }
    }

    @media (max-width: 767.98px) {
        .sp-wall {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .sp-reasons,
        .sp-package-grid,
        .sp-activation-grid {
            grid-template-columns: minmax(0, 1fr);
            gap: 18px;
        }

        .sp-bespoke {
            flex-direction: column;
        }

        .sp-allies__row--lead .sp-ally {
            flex-basis: 150px;
        }

        .sp-arrow--prev {
            inset-inline-start: -6px;
        }

        .sp-arrow--next {
            inset-inline-end: -6px;
        }

        .sp-closing {
            padding-block: 64px;
        }

        .sp-btn-light {
            gap: 20px;
            padding-inline: 24px;
        }
    }
</style>

{{-- Arrows of the "previous partners" band: rotate the logos through the
         two rows (4 + 6) so the layout always stays the same. --}}
<script>
    (function() {
        var stage = document.querySelector('[data-allies]');
        if (!stage) return;
        var rows = stage.querySelectorAll('.sp-allies__row');
        var a = rows[0],
            b = rows[1];
        stage.querySelector('[data-allies-next]').addEventListener('click', function() {
            a.appendChild(b.firstElementChild);
            b.appendChild(a.firstElementChild);
        });
        stage.querySelector('[data-allies-prev]').addEventListener('click', function() {
            a.insertBefore(b.lastElementChild, a.firstElementChild);
            b.insertBefore(a.lastElementChild, b.firstElementChild);
        });
    })();
</script>

@endsection