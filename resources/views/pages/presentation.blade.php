@extends('layouts.app')

@section('title', __('presentation.title'))
@section('description', __('presentation.intro.lede'))

@section('content')

    {{-- Stylesheet for the four bands below. Once it works, move this <link>
         into the <head> of layouts/app.blade.php (or your build pipeline). --}}
    <link rel="stylesheet" href="{{ asset('assets/css/presentation.css') }}">

    @php
        $year = $edition->year;

        // ---- Placeholder photographs: swap the paths here, nothing else. ----
        // Every image is cropped with object-fit: cover, so any ratio works.

        // Intro band — portrait photo on the right (speaker in a full hall).
        $introImage = ['src' => 'assets/images/presentation/popup.png', 'width' => 660, 'height' => 430];

        // Network band — world map on the left.
        $networkImage = ['src' => 'assets/images/presentation/carte.png', 'width' => 1200, 'height' => 800];

        // Audience card — crowd at the bottom of the card.
        $audienceImage = ['src' => 'assets/images/presentation/people.png', 'width' => 285, 'height' => 407];

        // Fallback logos for the two organisation cards (by position),
        // used when the organisation row has no logo of its own.
        $orgLogos = [
            ['src' => 'assets/images/presentation/logoarabiia.png', 'width' => 628, 'height' => 206],
            ['src' => 'assets/images/presentation/logo-iia-maroc.png', 'width' => 628, 'height' => 206],
        ];

        $highlightCards = [
            ['icon' => 'fa-bullseye', 'title' => __('presentation.highlights.objectives_title'), 'text' => __('presentation.highlights.objectives_text')],
            ['icon' => 'fa-lightbulb', 'title' => __('presentation.highlights.scope_title'), 'text' => __('presentation.highlights.scope_text')],
            ['icon' => 'fa-globe', 'title' => __('presentation.highlights.international_title'), 'text' => __('presentation.highlights.international_text')],
        ];

        $sheetRows = array_values(array_filter([
            ['icon' => 'fa-lightbulb', 'label' => __('presentation.sheet.theme'), 'value' => $edition->theme],
            ['icon' => 'fa-calendar-alt', 'label' => __('presentation.sheet.dates'), 'value' => $edition->dateLine($locale)],
            ['icon' => 'fa-map-marker-alt', 'label' => __('presentation.sheet.venue'), 'value' => $edition->venueLine($locale)],
            ['icon' => 'fa-flag', 'label' => __('presentation.sheet.organisers'), 'value' => $edition->organiser],
            ['icon' => 'fa-building', 'label' => __('presentation.sheet.host'), 'value' => $edition->host_institute],
            ['icon' => 'fa-language', 'label' => __('presentation.sheet.languages'), 'value' => __('presentation.languages_value')],
            ['icon' => 'fa-users', 'label' => __('presentation.sheet.audience'), 'value' => __('presentation.audience.brief')],
        ], static fn (array $row): bool => filled($row['value'])));

        $sheetDocument = $edition->documents()->published()->orderBy('sort_order')->orderBy('id')->first();

        // Static fallback when no organisation rows are published.
        $fallbackOrgs = [
            ['code' => 'ARABCIA', 'name' => 'ARABCIA', 'role' => __('presentation.organisations.organiser_role'), 'text' => __('presentation.organisations.organiser_desc')],
            ['code' => 'IIA_MAROC', 'name' => 'IIA Maroc', 'role' => __('presentation.organisations.host_role'), 'text' => __('presentation.organisations.host_desc')],
        ];
    @endphp

    {{-- The shared front-office hero. Previously `x-home.hero` verbatim, which put
         the organiser and the theme in the `h1` instead of the page's own
         subject — the reader could not tell from the first screen which page
         they had opened. --}}
    <x-front.hero
        :title="__('presentation.title')"
        :eyebrow="$edition->identityLabel()"
        :lede="__('presentation.intro.lede')"
        :crumbs="[__('nav.presentation') => null]"
        :facts="[
            ['icon' => 'fa-calendar-alt', 'label' => $edition->dateLine($locale)],
            ['icon' => 'fa-map-marker-alt',  'label' => $edition->venueLine($locale)],
        ]"
        :cta-label="$edition->registration_open ? __('nav.registration') : null"
        :cta-url="$edition->registration_open ? (auth()->check() ? route('pricing') : route('register')) : null"
        :secondary-label="__('nav.programme')"
        :secondary-url="route('programme')"
        image="assets/images/bg/about_page_bg.jpg" />

    <div class="p-page">

        {{-- Band 1 — introduction: text + 3 highlights on one side, portrait photo on the other.
             Grid columns mirror automatically in RTL (Arabic). --}}
        <section class="p-intro" id="presentation-intro" aria-labelledby="presentation-intro-title">
            <div class="container">
                <div class="p-intro__grid">

                    <div class="p-intro__body">
                        <h2 id="presentation-intro-title" class="p-intro__title">
                            @lang('presentation.intro.title', ['year' => $year])
                        </h2>

                        <p class="p-intro__lede">@lang('presentation.intro.lede')</p>

                        {{-- The theme, stated in full: three paragraphs read as the
                             page's argument before the reader reaches the cards. --}}
                        <div class="p-theme">
                            <h3 class="p-theme__title">@lang('presentation.intro.theme_title')</h3>
                            @foreach (__('presentation.intro.paragraphs') as $paragraph)
                                <p class="p-theme__text">{{ $paragraph }}</p>
                            @endforeach
                        </div>

                        <ul class="p-highlights" data-ux-stagger="90">
                            @foreach ($highlightCards as $card)
                                <li class="p-highlight">
                                    <span class="p-highlight__icon" aria-hidden="true">
                                        <i class="fas {{ $card['icon'] }}"></i>
                                    </span>
                                    <h3 class="p-highlight__title">{{ $card['title'] }}</h3>
                                    <p class="p-highlight__text">{{ $card['text'] }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <figure class="p-intro__media">
                        <img src="{{ asset($introImage['src']) }}"
                             width="{{ $introImage['width'] }}"
                             height="{{ $introImage['height'] }}"
                             alt="@lang('presentation.intro.alt')"
                             loading="lazy" decoding="async">
                    </figure>

                </div>
            </div>
        </section>

        {{-- Band 1.5 — the journey: three steps, one verb each. The arc reads
             left to right on desktop and top to bottom on a phone. --}}
        <section class="p-journey" id="presentation-journey" aria-labelledby="presentation-journey-title">
            <div class="container">
                <div class="p-head p-head--center">
                    <p class="p-eyebrow p-eyebrow--center">@lang('presentation.journey.eyebrow')</p>
                    <h2 id="presentation-journey-title" class="p-title">@lang('presentation.journey.title')</h2>
                    <p class="p-lede">@lang('presentation.journey.lede')</p>
                </div>

                <ol class="p-steps" data-ux-stagger="100">
                    @foreach (__('presentation.journey.steps') as $step)
                        <li class="p-step">
                            <span class="p-step__num" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="p-step__icon" aria-hidden="true"><i class="fas {{ $step['icon'] }}"></i></span>
                            <h3 class="p-step__title">{{ $step['title'] }}</h3>
                            <p class="p-step__text">{{ $step['text'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- Band 2 — organisations: heading on one side, two white cards on the other. --}}
        <section class="p-organisations" id="presentation-organisations" aria-labelledby="presentation-organisations-title">
            <div class="container">
                <div class="p-split">

                    <div class="p-split__body">
                        <p class="p-eyebrow">@lang('presentation.organisations.eyebrow')</p>
                        <h2 id="presentation-organisations-title" class="p-title">
                            @lang('presentation.organisations.title')
                        </h2>
                        <p class="p-lede">@lang('presentation.organisations.lede')</p>
                    </div>

                    <ul class="p-orgs" data-ux-stagger="90">
                        @forelse ($organisations as $organisation)
                            @php
                                $logo = $orgLogos[$loop->index] ?? $orgLogos[0];
                                $profile = __('presentation.organisations.profiles.'.$organisation->code);
                                $profile = is_array($profile) ? $profile : null;
                            @endphp
                            <li class="p-org">
                                <span class="p-org__mark">
                                    <img src="{{ asset($organisation->logo_path ?? $logo['src']) }}"
                                         width="{{ $logo['width'] }}" height="{{ $logo['height'] }}"
                                         alt="{{ $organisation->name }}"
                                         loading="lazy" decoding="async">
                                </span>

                                <div class="p-org__body">
                                    <h3 class="p-org__name">{{ $organisation->name }}</h3>
                                    @if ($organisation->role)
                                        <p class="p-org__role">{{ $organisation->role }}</p>
                                    @endif
                                    <p class="p-org__lede">{{ $organisation->description }}</p>

                                    @if ($profile)
                                        <dl class="p-org__stats">
                                            @foreach ($profile['stats'] as $stat)
                                                <div class="p-org__stat">
                                                    <dt>{{ $stat['label'] }}</dt>
                                                    <dd>{{ $stat['value'] }}</dd>
                                                </div>
                                            @endforeach
                                        </dl>

                                        @isset($profile['note'])
                                            <p class="p-org__note">{{ $profile['note'] }}</p>
                                        @endisset
                                    @endif
                                </div>

                                @if ($profile)
                                    <a class="p-org__cta" href="{{ $organisation->website_url ?: route('contact') }}"
                                       @if ($organisation->website_url) rel="noopener noreferrer" target="_blank" @endif>
                                        <span>{{ $profile['cta'] }}</span>
                                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                    </a>
                                @elseif ($organisation->website_url)
                                    <a class="p-org__link" href="{{ $organisation->website_url }}"
                                       rel="noopener noreferrer" target="_blank">
                                        <span class="visually-hidden">{{ $organisation->name }}</span>
                                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                    </a>
                                @endif
                            </li>
                        @empty
                            @foreach ($fallbackOrgs as $fallback)
                                @php
                                    $logo = $orgLogos[$loop->index];
                                    $profile = __('presentation.organisations.profiles.'.$fallback['code']);
                                    $profile = is_array($profile) ? $profile : null;
                                @endphp
                                <li class="p-org">
                                    <span class="p-org__mark">
                                        <img src="{{ asset($logo['src']) }}"
                                             width="{{ $logo['width'] }}" height="{{ $logo['height'] }}"
                                             alt="{{ $fallback['name'] }}"
                                             loading="lazy" decoding="async">
                                    </span>
                                    <div class="p-org__body">
                                        <h3 class="p-org__name">{{ $fallback['name'] }}</h3>
                                        <p class="p-org__role">{{ $fallback['role'] }}</p>
                                        <p class="p-org__lede">{{ $fallback['text'] }}</p>

                                        @if ($profile)
                                            <dl class="p-org__stats">
                                                @foreach ($profile['stats'] as $stat)
                                                    <div class="p-org__stat">
                                                        <dt>{{ $stat['label'] }}</dt>
                                                        <dd>{{ $stat['value'] }}</dd>
                                                    </div>
                                                @endforeach
                                            </dl>

                                            @isset($profile['note'])
                                                <p class="p-org__note">{{ $profile['note'] }}</p>
                                            @endisset
                                        @endif
                                    </div>
                                    @if ($profile)
                                        <a class="p-org__cta" href="{{ route('contact') }}">
                                            <span>{{ $profile['cta'] }}</span>
                                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        @endforelse
                    </ul>

                </div>
            </div>
        </section>

        {{-- Band 3 — network: rounded photo on one side, copy + outline button on the other. --}}
        <section class="p-network" aria-labelledby="presentation-network-title">
            <div class="container">
                <div class="p-network__grid">

                    <figure class="p-network__media">
                        <img src="{{ asset($networkImage['src']) }}"
                             width="{{ $networkImage['width'] }}"
                             height="{{ $networkImage['height'] }}"
                             alt="@lang('presentation.network.alt')"
                             loading="lazy" decoding="async">
                    </figure>

                    <div class="p-network__body">
                        <p class="p-eyebrow">@lang('presentation.network.eyebrow')</p>
                        <h2 id="presentation-network-title" class="p-title">
                            @lang('presentation.network.title')
                        </h2>
                        <p class="p-lede">@lang('presentation.network.lede')</p>

                        <a href="{{ route('sponsors') }}" class="p-btn-outline">
                            <span>@lang('presentation.network.cta')</span>
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>

                </div>
            </div>
        </section>

        {{-- Band 4 — technical sheet (purple panel) + target audience card. --}}
        <section class="p-sheet" aria-labelledby="presentation-sheet-title">
            <div class="container">
                <div class="p-sheet__grid">

                    <div class="p-sheet__panel ux-reveal">
                        <x-aurora :speed="0.4" />

                        <div class="p-sheet__inner">
                            <div class="p-sheet__head">
                                <span class="p-sheet__icon" aria-hidden="true"><i class="fas fa-file-alt"></i></span>
                                <div>
                                    <h2 id="presentation-sheet-title" class="p-sheet__title">@lang('presentation.sheet.title')</h2>
                                    <p class="p-sheet__lede">@lang('presentation.sheet.lede')</p>
                                </div>
                            </div>

                            <dl class="p-sheet__rows">
                                @foreach ($sheetRows as $row)
                                    <div class="p-sheet__row">
                                        <dt>
                                            <span class="p-sheet__row-icon" aria-hidden="true"><i class="fas {{ $row['icon'] }}"></i></span>
                                            <span>{{ $row['label'] }}</span>
                                        </dt>
                                        <dd>{{ $row['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>

                            @if ($sheetDocument)
                                <a href="{{ $sheetDocument->downloadUrl() }}" class="p-sheet__cta">
                                    <i class="fas fa-download" aria-hidden="true"></i>
                                    <span>@lang('presentation.download_pdf')</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="p-audience ux-reveal">
                        <div class="p-audience__body">
                            <p class="p-audience__eyebrow">
                                <i class="fas fa-users" aria-hidden="true"></i>
                                <span>@lang('presentation.audience.eyebrow')</span>
                            </p>
                            <h2 class="p-audience__title">@lang('presentation.audience.title')</h2>
                            <p class="p-audience__text">@lang('presentation.audience.lede')</p>
                        </div>

                        <figure class="p-audience__media">
                            <img src="{{ asset($audienceImage['src']) }}"
                                 width="{{ $audienceImage['width'] }}"
                                 height="{{ $audienceImage['height'] }}"
                                 alt="@lang('presentation.audience.alt')"
                                 loading="lazy" decoding="async">
                        </figure>
                    </div>

                </div>
            </div>
        </section>

    </div>

    {{-- The mockup goes straight from the sheet to the footer, so the
         <x-cta-band> that used to sit here has been removed. --}}

@endsection

<style>
    /* =========================================================================
   Presentation page — the four bands under the hero.
   Logical properties only, so French / English (LTR) and Arabic (RTL)
   share one stylesheet. Place in public/assets/css/presentation.css
   ========================================================================= */

.p-page {
    --p-ink: #1b1464;
    --p-brand: #4f3cc9;
    --p-muted: #5f6384;
    --p-tint: #ece8ff;
    --p-line: #ddd8f5;
    --p-panel-a: #6a4fd6;
    --p-panel-b: #3f2a9f;
    color: var(--p-ink);
}

.p-page section { position: relative; overflow: hidden; }
.p-page h2, .p-page h3, .p-page p, .p-page ul, .p-page dl { margin: 0; }
.p-page ul { list-style: none; padding: 0; }

/* RTL: mirror directional arrows */
[dir="rtl"] .p-page .fa-arrow-right { transform: scaleX(-1); }

/* ---------- Shared bits ---------- */
.p-eyebrow {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-block-end: 14px;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: var(--p-brand);
}
.p-eyebrow::after {
    content: "";
    width: 44px;
    height: 1px;
    background: currentColor;
    opacity: .45;
}
[dir="rtl"] .p-eyebrow { letter-spacing: 0; }

.p-title {
    margin-block-end: 16px;
    font-size: clamp(1.6rem, 2.6vw, 2.15rem);
    font-weight: 700;
    line-height: 1.2;
    color: var(--p-ink);
}
.p-lede {
    max-width: 46ch;
    font-size: .95rem;
    line-height: 1.75;
    color: var(--p-muted);
}

/* ---------- Band 1 — intro ---------- */
.p-intro {
    padding-block: 72px 64px;
    background: #fff;
}
.p-intro::after {
    content: "";
    position: absolute;
    inset-inline-start: -12%;
    inset-block-end: -140px;
    width: 65%;
    height: 260px;
    pointer-events: none;
}
.p-intro .container { position: relative; z-index: 1; }

.p-intro__grid {
    display: grid;
    grid-template-columns: minmax(0, 1.55fr) minmax(0, .8fr);
    gap: 56px;
    align-items: center;
}
.p-intro__title {
    max-width: 15em;
    font-size: clamp(2rem, 3.6vw, 2.9rem);
    font-weight: 700;
    line-height: 1.15;
    color: #14102e;
}
.p-intro__lede {
    max-width: 30em;
    margin-block: 26px 52px;
    font-size: 1.15rem;
    line-height: 1.75;
    color: #4a4d6b;
}
.p-intro__media {
    margin: 0;
    aspect-ratio: 4 / 5;
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 28px 50px -26px rgba(60, 40, 160, .5);
}
.p-intro__media img { width: 100%; height: 100%; object-fit: cover; display: block; }

.p-highlights {
    margin-top: 100px;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
}
.p-highlight { padding-inline: 22px; }
.p-highlight:first-child { padding-inline-start: 0; }
.p-highlight + .p-highlight { border-inline-start: 1px solid var(--p-line); }
.p-highlight__icon {
    display: grid;
    place-items: center;
    width: 52px;
    height: 52px;
    margin-block-end: 14px;
    border-radius: 14px;
    background: var(--p-tint);
    color: var(--p-brand);
    font-size: 1.2rem;
}
.p-highlight__title {
    margin-block-end: 6px;
    font-size: 1rem;
    font-weight: 700;
    color: #2a1f9d;
}
.p-highlight__text {
    font-size: .8rem;
    line-height: 1.55;
    color: var(--p-muted);
}

/* ---------- Centered heads ---------- */
.p-head--center { text-align: center; }
.p-eyebrow--center { justify-content: center; }
.p-head--center .p-lede { margin-inline: auto; }

/* ---------- Theme, inside Band 1 ---------- */
.p-theme { margin-block-start: 34px; }
.p-theme__title {
    margin-block-end: 12px;
    font-size: .82rem;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--p-brand);
}
[dir="rtl"] .p-theme__title { letter-spacing: 0; }
.p-theme__text {
    max-width: 60ch;
    margin-block-end: 12px;
    font-size: .9rem;
    line-height: 1.8;
    color: #4a4d6b;
}
.p-theme__text:last-child { margin-block-end: 0; }

/* ---------- Band 1.5 — journey ---------- */
.p-journey {
    padding-block: 72px;
    background: linear-gradient(180deg, #f6f4ff 0%, #fff 100%);
}
.p-steps {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22px;
    counter-reset: p-step;
}
.p-step {
    position: relative;
    padding: 32px 26px 28px;
    border-radius: 20px;
    background: #fff;
    border: 1px solid var(--p-line);
    box-shadow: 0 20px 44px -30px rgba(60, 40, 160, .45);
}
.p-step__num {
    position: absolute;
    inset-block-start: 22px;
    inset-inline-end: 24px;
    font-size: 1.6rem;
    font-weight: 700;
    line-height: 1;
    color: var(--p-tint);
}
.p-step__icon {
    display: grid;
    place-items: center;
    width: 52px;
    height: 52px;
    margin-block-end: 18px;
    border-radius: 15px;
    background: var(--p-tint);
    color: var(--p-brand);
    font-size: 1.2rem;
}
.p-step__title {
    margin-block-end: 8px;
    font-size: 1.05rem;
    font-weight: 700;
    color: #2a1f9d;
}
.p-step__text {
    font-size: .84rem;
    line-height: 1.65;
    color: var(--p-muted);
}

/* ---------- Band 2 — organisations ---------- */
.p-organisations {
    padding-block: 72px;
    background-image: url('../../../public/assets/images/presentation/slide.png');
        background-position: center;
    background-repeat: no-repeat;
    background-size: cover;

    /* ../images/presentation/slide.png */
}
.p-split {
    display: grid;
    grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr);
    gap: 48px;
    align-items: center;
}
.p-split__body .p-lede { max-width: 34ch; font-size: .88rem; }

.p-orgs {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}
.p-org {
    display: flex;
    flex-direction: column;
    gap: 18px;
    min-height: 260px;
    padding: 24px;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 18px 40px -22px rgba(60, 40, 160, .35);
}
.p-org__mark { display: block; height: 58px; }
.p-org__mark img { height: 100%; width: auto; max-width: 100%; object-fit: contain; object-position: start center; }
.p-org__name { font-size: 1rem; font-weight: 700; color: var(--p-ink); }
.p-org__role { margin-block: 2px 10px; font-size: .82rem; color: #8a8ca6; }
.p-org__lede { font-size: .8rem; line-height: 1.6; color: var(--p-muted); }
.p-org__link {
    display: grid;
    place-items: center;
    width: 34px;
    height: 34px;
    margin-block-start: auto;
    margin-inline-start: auto;
    border-radius: 50%;
    background: var(--p-tint);
    color: var(--p-brand);
    font-size: .8rem;
    text-decoration: none;
    transition: background-color .2s, color .2s;
}
a.p-org__link:hover, a.p-org__link:focus-visible { background: var(--p-brand); color: #fff; }

.p-org__stats {
    display: grid;
    gap: 6px;
    margin-block-start: 16px;
    padding-block-start: 14px;
    border-block-start: 1px solid var(--p-line);
}
.p-org__stat {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 12px;
    font-size: .78rem;
}
.p-org__stat dt { color: #8a8ca6; }
.p-org__stat dd { margin: 0; font-weight: 700; color: var(--p-ink); }
.p-org__note {
    margin-block-start: 12px;
    font-size: .74rem;
    line-height: 1.5;
    color: var(--p-brand);
}
.p-org__cta {
    display: inline-flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-block-start: auto;
    padding: 12px 20px;
    border-radius: 999px;
    background: var(--p-tint);
    font-size: .76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .02em;
    color: var(--p-brand);
    text-decoration: none;
    transition: background-color .2s, color .2s;
}
.p-org__cta:hover, .p-org__cta:focus-visible { background: var(--p-brand); color: #fff; }

/* ---------- Band 3 — network ---------- */
.p-network {
    padding-block: 64px;
    background: linear-gradient(180deg, #f3f1ff 0%, #fff 100%);
}
.p-network__grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.25fr);
    gap: 56px;
    align-items: center;
}
.p-network__media {
    margin: 0;
    aspect-ratio: 3 / 2;
    border-radius: 28px;
    border-end-end-radius: 72px;
    overflow: hidden;
}
.p-network__media img { width: 100%; height: 100%; object-fit: cover; display: block; }
.p-network__body .p-lede { max-width: 52ch; font-size: .88rem; }

.p-btn-outline {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-block-start: 26px;
    padding: 11px 24px;
    border: 1.5px solid var(--p-brand);
    border-radius: 999px;
    background: transparent;
    font-size: .82rem;
    font-weight: 600;
    color: var(--p-brand);
    text-decoration: none;
    transition: background-color .2s, color .2s;
}
.p-btn-outline:hover, .p-btn-outline:focus-visible { background: var(--p-brand); color: #fff; }

/* ---------- Band 4 — technical sheet + audience ---------- */
.p-sheet { padding-block: 40px 72px; background: #fff; }
.p-sheet__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 24px;
    align-items: stretch;
}

.p-sheet__panel {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    color: #fff;
    background: linear-gradient(135deg, var(--p-panel-a) 0%, var(--p-panel-b) 100%);
}
.p-sheet__inner { position: relative; padding: 40px 36px; }
.p-sheet__head { display: flex; align-items: center; gap: 16px; margin-block-end: 30px; }
.p-sheet__icon { font-size: 2rem; line-height: 1; opacity: .95; }
.p-sheet__title { font-size: 1.15rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #fff; }
[dir="rtl"] .p-sheet__title { letter-spacing: 0; }
.p-sheet__lede { margin-block-start: 2px; font-size: .85rem; color: rgba(255, 255, 255, .8); }

.p-sheet__rows { display: grid; gap: 14px; }
.p-sheet__row {
    display: grid;
    grid-template-columns: minmax(130px, 38%) minmax(0, 1fr);
    gap: 14px;
    align-items: center;
    font-size: .8rem;
}
.p-sheet__row dt { display: flex; align-items: center; gap: 14px; font-weight: 600; }
.p-sheet__row dd { margin: 0; color: rgba(255, 255, 255, .88); line-height: 1.45; }
.p-sheet__row-icon {
    display: grid;
    place-items: center;
    flex: none;
    width: 28px;
    height: 28px;
    border: 1.5px solid rgba(255, 255, 255, .55);
    border-radius: 50%;
    font-size: .7rem;
}

.p-sheet__cta {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-block-start: 34px;
    padding: 13px 28px;
    border-radius: 999px;
    background: #fff;
    font-size: .82rem;
    font-weight: 700;
    color: #2a1f9d;
    text-decoration: none;
    transition: transform .2s;
}
.p-sheet__cta:hover, .p-sheet__cta:focus-visible { transform: translateY(-2px); }

.p-audience {
    display: flex;
    flex-direction: column;
    border-radius: 20px;
    overflow: hidden;
    background: linear-gradient(180deg, #f6f4ff 0%, #e9e5ff 100%);
}
.p-audience__body { padding: 40px 40px 24px; }
.p-audience__eyebrow {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-block-end: 18px;
    font-size: .85rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #2a1f9d;
}
.p-audience__eyebrow i { font-size: 1.4rem; color: var(--p-brand); }
[dir="rtl"] .p-audience__eyebrow { letter-spacing: 0; }
.p-audience__title {
    margin-block-end: 16px;
    font-size: clamp(1.3rem, 2vw, 1.65rem);
    font-weight: 700;
    line-height: 1.3;
    color: var(--p-ink);
}
.p-audience__text { max-width: 46ch; font-size: .8rem; line-height: 1.75; color: var(--p-muted); }
.p-audience__media {
    position: relative;
    margin: auto 0 0;
    margin-top: 30% !important;
    overflow: hidden;
    clip-path: ellipse(80% 100% at 50% 100%);
}
.p-audience__media img { width: 100%; height: 100%; object-fit: cover; display: block; }

/* ---------- Responsive ---------- */
@media (max-width: 991.98px) {
    .p-intro__grid,
    .p-split,
    .p-network__grid,
    .p-sheet__grid,
    .p-steps { grid-template-columns: minmax(0, 1fr); gap: 36px; }
    .p-intro__media { aspect-ratio: 16 / 10; }
}
@media (max-width: 640px) {
    .p-highlights { grid-template-columns: minmax(0, 1fr); gap: 24px; }
    .p-highlight, .p-highlight:first-child { padding-inline: 0; }
    .p-highlight + .p-highlight { border-inline-start: 0; border-block-start: 1px solid var(--p-line); padding-block-start: 24px; }
    .p-orgs { grid-template-columns: minmax(0, 1fr); }
    .p-sheet__inner, .p-audience__body { padding: 28px 22px; }
    .p-sheet__row { grid-template-columns: minmax(0, 1fr); gap: 4px; }
}
</style>