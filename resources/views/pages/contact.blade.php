@extends('layouts.app')

@section('title', __('contact.title'))
@section('description', __('contact.intro'))

@section('content')

@php
    /*
     * Data used by the sections below. Everything has a fallback so the page
     * renders even before the controller is updated (see the notes).
     */
    $selectedTopic = old('subject_type', request('topic'));

    $helpCards = [
        ['topic' => 'registration', 'icon' => 'fa fa-users',       'tone' => 'lilac',
         'title' => __('contact.card_registration_title'), 'text' => __('contact.card_registration_text'), 'cta' => __('contact.card_cta')],
        ['topic' => 'sponsoring',   'icon' => 'fa-handshake',        'tone' => 'amber',
         'title' => __('contact.card_sponsoring_title'),   'text' => __('contact.card_sponsoring_text'),   'cta' => __('contact.card_sponsoring_cta')],
        ['topic' => 'speaker',      'icon' => 'fa fa-file','tone' => 'lilac',
         'title' => __('contact.card_programme_title'),    'text' => __('contact.card_programme_text'),    'cta' => __('contact.card_cta')],
    ];

    $countries = $countries ?? [
        'Maroc', 'Algérie', 'Tunisie', 'Égypte', 'Arabie saoudite', 'Émirats arabes unis', 'Qatar', 'Koweït',
        'Bahreïn', 'Oman', 'Jordanie', 'Liban', 'Irak', 'Mauritanie', 'Libye', 'Soudan', 'Palestine',
        'France', 'Espagne', 'Belgique', 'Royaume-Uni', 'États-Unis', 'Autre',
    ];

    $contactEmail = $contactEmail ?? config('brand.contact_email', 'contact@arabcia2026.ma');
    $registerUrl  = $registerUrl  ?? url('/inscription');
    $bannerImage  = $bannerImage ?? 'https://images.unsplash.com/photo-1597212618440-806262de4f6b?auto=format&fit=crop&w=1800&q=80'; // Hassan Tower, Rabat
    $venueImage   = 'https://images.unsplash.com/photo-1489749798305-4fea3ae63d43?auto=format&fit=crop&w=1200&q=80';
@endphp

<div class="d-page d-page--contact">

    {{-- ================= HERO (unchanged) ================= --}}
    <x-front.hero
        :title="__('contact.title')"
        :eyebrow="$edition?->identityLabel()"
        :lede="__('contact.hero_lede')"
        :crumbs="[__('nav.contact') => null]"
        :facts="$edition ? [
            ['icon' => 'fa-calendar-alt', 'label' => $edition->dateLine($locale)],
            ['icon' => 'fa-map-marker-alt',  'label' => $edition->venueLine($locale)],
        ] : []"
        :cta-label="$edition?->mapUrl() ? __('contact.open_map') : null"
        :cta-url="$edition?->mapUrl()"
        :cta-url-external="(bool) $edition?->mapUrl()"
        :secondary-label="__('programme.title')"
        :secondary-url="route('programme')"
        image="assets/images/bg/form_bg.jpg" />

    {{-- ================= 0. CONTACT BANNER (Hassan Tower) ================= --}}
    <section class="c-banner" aria-labelledby="banner-heading">
        <div class="c-banner__photo"  style="background-image: url('{{ asset('assets/images/devenezsponsor.png') }}'); background-size: cover; background-position: center; background-repeat: no-repeat;" aria-hidden="true"></div>

        <div class="container c-banner__inner">
            <p class="c-banner__eyebrow">{{ __('nav.contact') }}</p>

            <h1 class="c-banner__title" id="banner-heading">
                {{ __('contact.banner_title_a') }}
                <span class="c-banner__accent">{{ __('contact.banner_title_b') }}</span>
            </h1>

            <p class="c-banner__lede">{{ __('contact.banner_lede') }}</p>

            @if ($edition)
                <ul class="c-banner__facts">
                    <!-- <li>
                        <span class="c-banner__fact-icon" aria-hidden="true"><i class="fas fa-calendar-days"></i></span>
                        <strong>{{ $edition->dateLine($locale) }}</strong>
                    </li>
                    <li>
                        <span class="c-banner__fact-icon" aria-hidden="true"><i class="fas fa-location-dot"></i></span>
                        <strong>{{ $edition->venueLine($locale) }}</strong>
                    </li> -->
                </ul>
            @endif
        </div>
    </section>

    {{-- ================= 1. CHOOSE THE TYPE OF REQUEST ================= --}}
    <section class="c-section c-help" aria-labelledby="help-heading">
        <div class="container">
            <header class="c-head">
                <p class="c-eyebrow">{{ __('contact.help_eyebrow') }}</p>
                <h2 class="c-title" id="help-heading">
                    {{ __('contact.help_title_a') }}
                    <span class="c-title__accent">{{ __('contact.help_title_b') }}</span>
                </h2>
            </header>

            <ul class="c-help__grid">
                @foreach ($helpCards as $card)
                    <li class="c-help__item">
                        <article class="c-help__card c-help__card--{{ $card['tone'] }}">
                            <span class="c-help__icon" aria-hidden="true">
                                <i class="fas {{ $card['icon'] }}"></i>
                            </span>

                            <div class="c-help__body">
                                <h3 class="c-help__title">{{ $card['title'] }}</h3>
                                <p class="c-help__text">{{ $card['text'] }}</p>
                            </div>

                            <a href="#contact-form" class="c-help__link" data-c-topic="{{ $card['topic'] }}">
                                <span>{{ $card['cta'] }}</span>
                                <span class="c-help__arrow" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                            </a>
                        </article>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ================= 2. THE TEAM ================= --}}
    @if ($team->isNotEmpty())
        <section class="c-section c-team" aria-labelledby="team-heading">
            <div class="container">
                <header class="c-head">
                    <p class="c-eyebrow">{{ __('contact.team_eyebrow') }}</p>
                    <h2 class="c-title" id="team-heading">{{ __('contact.team_title') }}</h2>
                    <p class="c-lede">{{ __('contact.team_lede') }}</p>
                </header>

                <ul class="c-team__grid">
                    @foreach ($team as $member)
                        <li>
                            <article class="c-member">
                                <span class="c-member__avatar" aria-hidden="true"><i class="fas fa-user"></i></span>

                                <div class="c-member__body">
                                    <h3 class="c-member__name">{{ $member->name }}</h3>
                                    @if ($member->role)
                                        <p class="c-member__role">{{ $member->role }}</p>
                                    @endif

                                    <ul class="c-member__lines">
                                        @if ($member->phone)
                                            <li>
                                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $member->phone) }}" dir="ltr">
                                                    <i class="fas fa-phone" aria-hidden="true"></i>
                                                    <span>{{ $member->phone }}</span>
                                                </a>
                                            </li>
                                        @endif
                                        @if ($member->email)
                                            <li>
                                                <a href="mailto:{{ $member->email }}" dir="ltr">
                                                    <i class="fas fa-envelope" aria-hidden="true"></i>
                                                    <span>{{ $member->email }}</span>
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ================= 3. FORM + PRACTICAL INFORMATION ================= --}}
    <section class="c-section c-contact" id="contact-form" aria-labelledby="form-heading">
        <div class="container">
            <div class="c-contact__grid">

                {{-- ---- Form ---- --}}
                <div class="c-contact__form">
                    <p class="c-eyebrow c-eyebrow--start">{{ __('contact.form_kicker') }}</p>
                    <h2 class="c-title c-title--start" id="form-heading">{{ __('contact.form_heading') }}</h2>
                    <p class="c-lede c-lede--start">{{ __('contact.form_intro') }}</p>

                    <form method="POST" action="{{ route('contact.store') }}" class="c-form" novalidate>
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <x-form.field name="name" :label="__('contact.full_name')" required>
                                    <input type="text" name="name" id="name" required autocomplete="name"
                                           placeholder="{{ __('contact.ph_name') }}" value="{{ old('name') }}"
                                           @class(['c-input', 'is-invalid' => $errors->has('name')])>
                                </x-form.field>
                            </div>

                            <div class="col-md-6">
                                <x-form.field name="organisation" :label="__('contact.organisation_required')" required>
                                    <input type="text" name="organisation" id="organisation" required autocomplete="organization"
                                           placeholder="{{ __('contact.ph_organisation') }}" value="{{ old('organisation') }}"
                                           @class(['c-input', 'is-invalid' => $errors->has('organisation')])>
                                </x-form.field>
                            </div>

                            <div class="col-md-6">
                                <x-form.field name="job_title" :label="__('contact.job_title')">
                                    <input type="text" name="job_title" id="job_title" autocomplete="organization-title"
                                           placeholder="{{ __('contact.ph_job_title') }}" value="{{ old('job_title') }}"
                                           class="c-input">
                                </x-form.field>
                            </div>

                            <div class="col-md-6">
                                <x-form.field name="country" :label="__('contact.country')" required>
                                    <select name="country" id="country" required autocomplete="country-name"
                                            @class(['c-input', 'c-select', 'is-invalid' => $errors->has('country')])>
                                        <option value="" disabled @selected(old('country') === null)>{{ __('contact.choose_country') }}</option>
                                        @foreach ($countries as $country)
                                            <option value="{{ $country }}" @selected(old('country') === $country)>{{ $country }}</option>
                                        @endforeach
                                    </select>
                                </x-form.field>
                            </div>

                            <div class="col-md-6">
                                <x-form.field name="email" :label="__('contact.email_short')" required>
                                    <input type="email" name="email" id="email" required autocomplete="email" dir="ltr"
                                           placeholder="{{ __('contact.ph_email') }}" value="{{ old('email') }}"
                                           @class(['c-input', 'is-invalid' => $errors->has('email')])>
                                </x-form.field>
                            </div>

                            <div class="col-md-6">
                                <x-form.field name="phone" :label="__('contact.phone_short')">
                                    <input type="tel" name="phone" id="phone" autocomplete="tel" dir="ltr"
                                           placeholder="{{ __('contact.ph_phone') }}" value="{{ old('phone') }}"
                                           class="c-input text-start">
                                </x-form.field>
                            </div>

                            <div class="col-12">
                                <x-form.field name="subject_type" :label="__('contact.topic_label')" required>
                                    <select name="subject_type" id="subject_type" required
                                            @class(['c-input', 'c-select', 'is-invalid' => $errors->has('subject_type')])>
                                        @foreach ($topics as $topic)
                                            <option value="{{ $topic['value'] }}" @selected($selectedTopic === $topic['value'])>{{ $topic['label'] }}</option>
                                        @endforeach
                                    </select>
                                </x-form.field>
                            </div>

                            <div class="col-12">
                                <x-form.field name="message" :label="__('contact.your_message')" required>
                                    <textarea name="message" id="message" rows="5" required maxlength="5000"
                                              placeholder="{{ __('contact.ph_message') }}"
                                              @class(['c-input', 'is-invalid' => $errors->has('message')])>{{ old('message') }}</textarea>
                                </x-form.field>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="c-submit">
                                    <span>{{ __('contact.send_request') }}</span>
                                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- ---- Practical information ---- --}}
                <aside class="c-info" aria-labelledby="info-heading"   style="height:64% ; background-image: url('{{ asset('assets/images/background.png') }}');; background-size: cover; background-position: center; background-repeat: no-repeat;">
                    <div class="c-info__inner">
                        <h2 class="c-info__title" id="info-heading">{{ __('contact.info_title') }}</h2>

                        <ul class="c-info__list">
                            @if ($edition)
                                <li>
                                    <span class="c-info__icon" aria-hidden="true"><i class="fas fa-location-dot"></i></span>
                                    <div>
                                        <strong>{{ $edition->venueLine($locale) }}</strong>
                                    </div>
                                </li>
                                <li>
                                    <span class="c-info__icon" aria-hidden="true"><i class="fas fa-calendar-days"></i></span>
                                    <div><strong>{{ $edition->dateLine($locale) }}</strong></div>
                                </li>
                            @endif
                            <li>
                                <span class="c-info__icon" aria-hidden="true"><i class="fas fa-envelope"></i></span>
                                <div>
                                    <strong>{{ __('contact.general_contact') }}</strong>
                                    <a href="mailto:{{ $contactEmail }}" dir="ltr">{{ $contactEmail }}</a>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- <figure class="c-info__photo">
                        <img src="{{ asset('assets/images/background.png') }}" alt="" loading="lazy" decoding="async">
                    </figure> -->
                </aside>
            </div>
        </div>
    </section>

    {{-- ================= 4. CLOSING BAND ================= --}}
    <section class="c-cta" aria-label="{{ __('contact.cta_kicker') }}">
        <div class="container">
            <div class="c-cta__inner">
                <div class="c-cta__when">
                    <span class="c-cta__icon" aria-hidden="true"><i class="fas fa-calendar-days"></i></span>
                    <div>
                        <p class="c-cta__kicker">{{ __('contact.cta_kicker') }}</p>
                        <p class="c-cta__date">{{ $edition?->dateLine($locale) }}</p>
                    </div>
                </div>

                <p class="c-cta__text">{{ __('contact.cta_text') }}</p>

                <a href="{{ $registerUrl }}" class="c-cta__btn">
                    <span>{{ __('contact.cta_button') }}</span>
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </section>

</div>

<script>
    /* "Nous contacter" on a card: preselect the topic, then let the anchor scroll. */
    document.querySelectorAll('[data-c-topic]').forEach(function (link) {
        link.addEventListener('click', function () {
            var select = document.getElementById('subject_type');
            if (select) { select.value = link.dataset.cTopic; }
        });
    });
</script>

@endsection
<style>
    /* ==========================================================================
   24. Contact page — middle sections (prefix `c-`)
   Append to the end of design.css. New classes only; nothing existing is
   redefined. Logical properties throughout (Arabic is the default locale).
   ========================================================================== */

:root {
    --c-accent: #6d4fd6;          /* "le type de demande", roles */
    --c-eyebrow: #6b6fe0;
    --c-lilac-card: #f1f0fb;
    --c-lilac-circle: #e3e1f7;
    --c-amber: #cf8a2a;
    --c-amber-card: #fdf6e9;
    --c-amber-circle: #fae7c6;
    --c-field-line: #d8d5ea;
}

.c-section { padding-block: clamp(2.5rem, 5vw, 4rem); }

/* ---------- shared heading block ---------- */
.c-head { margin-block-end: var(--sp-6); text-align: center; }

.c-eyebrow {
    color: var(--c-eyebrow);
    font-size: var(--t-xs);
    font-weight: 700;
    letter-spacing: .2em;
    margin: 0 0 var(--sp-2);
    text-transform: uppercase;
}
body.rtl .c-eyebrow { letter-spacing: .04em; }

.c-eyebrow--start::after {            /* the little dash under the form eyebrow */
    background: var(--c-accent);
    border-radius: 2px;
    content: '';
    display: block;
    height: 2px;
    margin-block-start: .4rem;
    width: 1.6rem;
}

.c-title {
    color: var(--d-ink);
    font-size: clamp(1.6rem, 1.3rem + 1.4vw, 2.25rem);
    margin: 0;
}
.c-title--start { text-align: start; }
.c-title__accent { color: var(--c-accent); }

.c-lede {
    color: var(--d-muted);
    font-size: var(--t-base);
    margin: var(--sp-3) auto 0;
    max-width: 52rem;
}
.c-lede--start { margin-inline: 0; text-align: start; }

/* ---------- 1. help cards ---------- */
.c-help__grid {
    display: grid;
    gap: var(--sp-4);
    grid-template-columns: repeat(3, minmax(0, 1fr));
    list-style: none;
    margin: 0;
    padding: 0;
}
@media (max-width: 991.98px) { .c-help__grid { grid-template-columns: 1fr; } }

.c-help__item { display: flex; }

.c-help__card {
    background: var(--c-lilac-card);
    border-radius: var(--r-md);
    column-gap: var(--sp-4);
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    padding: var(--sp-5);
    width: 100%;
}

.c-help__icon {
    align-items: center;
    background: var(--c-lilac-circle);
    border-radius: 50%;
    color: var(--d-indigo);
    display: flex;
    font-size: 1.7rem;
    grid-row: 1 / span 2;
    height: 4.5rem;
    justify-content: center;
    width: 4.5rem;
}

.c-help__title { color: var(--d-ink); font-size: var(--t-lg); margin: 0 0 var(--sp-2); }
.c-help__text { color: var(--d-muted); font-size: var(--t-sm); line-height: 1.6; margin: 0; }

.c-help__link {
    align-items: center;
    color: var(--d-indigo);
    display: flex;
    font-size: var(--t-xs);
    font-weight: 800;
    grid-column: 2;
    justify-content: space-between;
    letter-spacing: .03em;
    margin-block-start: var(--sp-4);
    text-decoration: none;
    text-transform: uppercase;
}

.c-help__arrow {
    align-items: center;
    border: 2px solid currentColor;
    border-radius: 50%;
    display: inline-flex;
    height: 2.4rem;
    justify-content: center;
    transition: background-color var(--dur-2) var(--ease), color var(--dur-2) var(--ease);
    width: 2.4rem;
}
.c-help__link:hover .c-help__arrow,
.c-help__link:focus-visible .c-help__arrow { background: currentColor; }
.c-help__link:hover .c-help__arrow i,
.c-help__link:focus-visible .c-help__arrow i { color: #fff; }

.c-help__card--amber { background: var(--c-amber-card); }
.c-help__card--amber .c-help__icon { background: var(--c-amber-circle); color: var(--c-amber); }
.c-help__card--amber .c-help__link { color: var(--c-amber); }

@media (max-width: 575.98px) {
    .c-help__card { grid-template-columns: 1fr; row-gap: var(--sp-3); }
    .c-help__icon { grid-row: auto; }
    .c-help__link { grid-column: 1; }
}

/* ---------- 2. team ---------- */
.c-team { background: #f2f1fb; }

.c-team__grid {
    display: grid;
    gap: var(--sp-4);
    grid-template-columns: repeat(3, minmax(0, 1fr));
    list-style: none;
    margin: 0;
    padding: 0;
}
@media (max-width: 991.98px) { .c-team__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 575.98px) { .c-team__grid { grid-template-columns: 1fr; } }

.c-member {
    align-items: flex-start;
    background: #fff;
    border-radius: var(--r-md);
    box-shadow: 0 2px 10px rgb(42 31 110 / 6%);
    display: flex;
    gap: var(--sp-4);
    height: 100%;
    padding: var(--sp-5) var(--sp-4);
}

.c-member__avatar {
    align-items: center;
    background: #e9e7f8;
    border-radius: 50%;
    color: var(--d-indigo);
    display: flex;
    flex: 0 0 auto;
    height: 3.25rem;
    justify-content: center;
    width: 3.25rem;
}

.c-member__body { min-width: 0; }
.c-member__name { color: var(--d-ink); font-size: var(--t-base); line-height: 1.3; margin: 0; }
.c-member__role { color: var(--c-accent); font-size: var(--t-sm); margin: 2px 0 var(--sp-3); }

.c-member__lines { display: grid; gap: var(--sp-2); list-style: none; margin: 0; padding: 0; }
.c-member__lines a {
    align-items: center;
    color: var(--d-ink);
    display: flex;
    font-size: var(--t-sm);
    gap: var(--sp-2);
    overflow-wrap: anywhere;
    text-decoration: none;
}
.c-member__lines a:hover, .c-member__lines a:focus-visible { color: var(--c-accent); text-decoration: underline; }
.c-member__lines i { color: var(--d-indigo); flex: 0 0 auto; font-size: .95em; width: 1.1em; }

/* ---------- 3. form + info ---------- */
.c-contact__grid {
    align-items: start;
    display: grid;
    gap: var(--sp-7);
    grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr);
}
@media (max-width: 991.98px) { .c-contact__grid { grid-template-columns: 1fr; } }

.c-form { margin-block-start: var(--sp-5); }

.c-form .form-label {
    color: var(--d-ink);
    font-size: var(--t-xs);
    font-weight: 700;
    margin: 0 0 .35rem;
}

.c-input {
    background-color: #fff;
    border: 1px solid var(--c-field-line);
    border-radius: 6px;
    color: var(--d-ink);
    font-family: inherit;
    font-size: var(--t-sm);
    min-height: 2.9rem;
    padding: .6rem .9rem;
    transition: border-color var(--dur-2) var(--ease), box-shadow var(--dur-2) var(--ease);
    width: 100%;
}
.c-input::placeholder { color: #9d99ba; }
.c-input:focus { border-color: var(--d-periwinkle); box-shadow: 0 0 0 4px rgb(115 132 255 / 18%); outline: none; }
.c-input.is-invalid { border-color: #d4483e; }
textarea.c-input { min-height: 7.5rem; resize: vertical; }

.c-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%232a1f6e' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-position: right .9rem center;
    background-repeat: no-repeat;
    padding-inline-end: 2.4rem;
}
[dir="rtl"] .c-select { background-position: left .9rem center; }

.c-form .field-error, .c-form .invalid-feedback { color: #b3261e; font-size: var(--t-xs); margin-block-start: .25rem; }

.c-submit {
    align-items: center;
    background: var(--d-indigo);
    border: 0;
    border-radius: 8px;
    color: #fff;
    cursor: pointer;
    display: inline-flex;
    font-family: inherit;
    font-size: var(--t-xs);
    font-weight: 800;
    gap: var(--sp-5);
    letter-spacing: .04em;
    padding: .95rem 1.5rem;
    text-transform: uppercase;
    transition: background-color var(--dur-2) var(--ease), transform var(--dur-2) var(--ease), box-shadow var(--dur-2) var(--ease);
}
.c-submit:hover { background: var(--d-indigo-dark); box-shadow: var(--sh-brand); transform: translateY(-2px); }
@media (max-width: 575.98px) { .c-submit { justify-content: space-between; width: 100%; } }

/* info panel */
.c-info {
    background: #f3f2fc;
    border-radius: var(--r-md);
    overflow: hidden;
}
@media (min-width: 992px) { .c-info {  top: 6.5rem; } }

.c-info__inner { padding: var(--sp-6) var(--sp-6) var(--sp-5); }
.c-info__title { color: var(--d-ink); font-size: var(--t-xl); margin: 0 0 var(--sp-5); }

.c-info__list { display: grid; gap: var(--sp-5); list-style: none; margin: 0; padding: 0; }
.c-info__list li { align-items: flex-start; display: flex; gap: var(--sp-4); }
.c-info__icon { color: var(--d-indigo); flex: 0 0 auto; font-size: 1.6rem; text-align: center; width: 2rem; }
.c-info__list strong { color: var(--d-ink); display: block; font-size: var(--t-base); line-height: 1.4; }
.c-info__list a { color: var(--d-ink); font-size: var(--t-sm); text-decoration: none; }
.c-info__list a:hover { color: var(--c-accent); text-decoration: underline; }

.c-info__photo { margin: 0; }
.c-info__photo img { aspect-ratio: 16 / 9; display: block; object-fit: cover; width: 100%; }

/* ---------- 4. closing band ---------- */
.c-cta {
    background-color: var(--d-navy-lift);
    background-image: linear-gradient(168deg, var(--d-navy-lift) 0%, #26116e 54%, var(--d-navy) 100%);
    color: rgb(255 255 255 / 82%);
    padding-block: var(--sp-6);
}

.c-cta__inner {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-5) var(--sp-6);
}

.c-cta__when { align-items: center; display: flex; gap: var(--sp-4); }
.c-cta__icon { color: var(--d-periwinkle); font-size: 2.2rem; }
.c-cta__kicker { font-size: var(--t-xs); font-weight: 700; letter-spacing: .14em; margin: 0; text-transform: uppercase; }
body.rtl .c-cta__kicker { letter-spacing: .02em; }
.c-cta__date { color: #fff; font-family: var(--f-title); font-size: var(--t-xl); font-weight: 700; line-height: 1.2; margin: 0; }

.c-cta__text {
    border-inline-start: 1px solid rgb(255 255 255 / 28%);
    flex: 1 1 18rem;
    font-size: var(--t-sm);
    margin: 0;
    padding-inline-start: var(--sp-6);
}
@media (max-width: 767.98px) { .c-cta__text { border: 0; padding: 0; } }

.c-cta__btn {
    align-items: center;
    background: #fff;
    border-radius: 10px;
    color: var(--d-indigo);
    display: inline-flex;
    font-size: var(--t-xs);
    font-weight: 800;
    gap: var(--sp-4);
    padding: .95rem 1.5rem;
    text-decoration: none;
    text-transform: uppercase;
    transition: background-color var(--dur-2) var(--ease), transform var(--dur-2) var(--ease);
}
.c-cta__btn:hover { background: var(--d-periwinkle); color: #fff; transform: translateY(-2px); }
@media (max-width: 575.98px) { .c-cta__btn { justify-content: space-between; width: 100%; } }

/* ==========================================================================
   24b. Contact banner (Hassan Tower) + polish pass
   ========================================================================== */

:root {
    --c-navy-text: #1c1470;
    --c-purple: #7a3fe0;
    --c-fade: to left;
}
[dir="rtl"] { --c-fade: to right; }

.c-banner {
    background:
        radial-gradient(90% 120% at 0% 100%, rgb(150 130 255 / 38%) 0%, transparent 60%),
        linear-gradient(135deg, #f1edff 0%, #e4defb 55%, #d9d1f8 100%);
    isolation: isolate;
    overflow: hidden;
    padding-block: clamp(3rem, 6vw, 4.75rem);
    position: relative;
}

/* The photograph hugs the inline-end side and dissolves into the lavender
   toward the copy, which is what makes it read as one surface. */
.c-banner__photo {
    background-position: center;
    background-size: cover;
    inset-block: 0;
    inset-inline-end: 0;
    -webkit-mask-image: linear-gradient(var(--c-fade), #000 58%, transparent 100%);
    mask-image: linear-gradient(var(--c-fade), #000 58%, transparent 100%);
    position: absolute;
    width: min(68%, 900px);
    z-index: -1;
}

/* Soft wave at the foot, like the mock-up's lavender ribbon. */
.c-banner::after {
    background: radial-gradient(70% 100% at 10% 120%, rgb(140 120 255 / 45%) 0%, transparent 70%);
    content: '';
    height: 45%;
    inset-block-end: 0;
    inset-inline: 0;
    pointer-events: none;
    position: absolute;
    z-index: -1;
}

.c-banner__inner { max-width: none; }
.c-banner__inner > * { max-width: 38rem; }

.c-banner__eyebrow {
    align-items: center;
    color: var(--c-purple);
    display: flex;
    font-size: var(--t-xs);
    font-weight: 700;
    gap: var(--sp-4);
    letter-spacing: .2em;
    margin: 0 0 var(--sp-3);
    text-transform: uppercase;
}
.c-banner__eyebrow::before { background: var(--c-purple); content: ''; height: 2px; width: 3rem; }
body.rtl .c-banner__eyebrow { letter-spacing: .04em; }

.c-banner__title {
    color: var(--c-navy-text);
    font-size: clamp(2rem, 1.4rem + 2.6vw, 3.15rem);
    font-weight: 800;
    line-height: 1.08;
    margin: 0 0 var(--sp-4);
}
body.rtl .c-banner__title { line-height: 1.35; }
.c-banner__accent { color: var(--c-purple); display: block; }

.c-banner__lede {
    color: var(--c-navy-text);
    font-size: var(--t-base);
    line-height: 1.65;
    margin: 0 0 var(--sp-5);
    max-width: 36rem;
}

.c-banner__facts {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-4) 0;
    list-style: none;
    margin: 0;
    padding: 0;
}
.c-banner__facts li { align-items: center; color: var(--c-navy-text); display: flex; gap: var(--sp-3); }
.c-banner__facts li + li { border-inline-start: 1px solid rgb(42 31 110 / 25%); margin-inline-start: var(--sp-5); padding-inline-start: var(--sp-5); }
.c-banner__facts strong { font-size: var(--t-base); line-height: 1.3; }
.c-banner__fact-icon { color: #5b3fd9; font-size: 1.9rem; line-height: 1; }

@media (max-width: 767.98px) {
    .c-banner__photo { opacity: .35; width: 100%; }
    .c-banner__facts li + li { border: 0; margin: 0; padding: 0; }
}

/* ---------- polish: help cards ---------- */
.c-help { padding-block-start: clamp(2rem, 4vw, 3rem); }

.c-help__card {
    background: linear-gradient(135deg, #f5f4ff 0%, #ebe9fb 100%);
    border: 1px solid #e4e1f8;
    transition: box-shadow var(--dur-2) var(--ease), transform var(--dur-2) var(--ease);
}
.c-help__card:hover { box-shadow: var(--sh-2); transform: translateY(-4px); }
.c-help__card--amber { background: linear-gradient(135deg, #fff9ee 0%, #fdf1da 100%); border-color: #f6e5c4; }

.c-help__icon { font-size: 1.9rem; height: 5rem; width: 5rem; }
.c-help__title { color: var(--c-navy-text); font-size: 1.15rem; font-weight: 800; }
.c-help__text { color: #4a4478; }

/* ---------- polish: title accent + team ---------- */
.c-title { color: var(--c-navy-text); font-weight: 800; }
.c-title__accent { color: var(--c-purple); }

.c-team { background: linear-gradient(180deg, #f3f2fd 0%, #eceafb 100%); }
.c-member { border: 1px solid #ece9fb; transition: box-shadow var(--dur-2) var(--ease), transform var(--dur-2) var(--ease); }
.c-member:hover { box-shadow: var(--sh-2); transform: translateY(-3px); }
.c-member__name { color: var(--c-navy-text); font-weight: 800; }
.c-member__role { color: var(--c-purple); font-weight: 500; }
.c-member__lines a { color: var(--c-navy-text); }
.c-member__lines i { color: #5b3fd9; }

/* ---------- polish: form + info ---------- */
.c-form .form-label { color: var(--c-navy-text); }
.c-submit { background: linear-gradient(135deg, #2f27b8 0%, #231a9a 100%); border-radius: 8px; min-width: 16rem; justify-content: space-between; }

.c-info {
    background:
        radial-gradient(60% 40% at 100% 0%, rgb(115 132 255 / 18%) 0%, transparent 70%),
        linear-gradient(180deg, #f4f3fe 0%, #ecebfb 100%);
    border: 1px solid #e4e1f8;
}
.c-info__title { color: var(--c-navy-text); font-weight: 800; }
.c-info__icon { color: #3b2fc9; }
.c-info__list strong { color: var(--c-navy-text); }
.c-info__photo { position: relative; }
.c-info__photo::before {          /* the photo melts up into the panel */
    background: linear-gradient(180deg, #ecebfb 0%, transparent 100%);
    content: '';
    height: 35%;
    inset: 0 0 auto 0;
    position: absolute;
}

/* ---------- polish: closing band ---------- */
.c-cta {
    background-image:
        radial-gradient(40% 160% at 0% 50%, rgb(115 132 255 / 22%) 0%, transparent 70%),
        linear-gradient(168deg, var(--d-navy-lift) 0%, #26116e 54%, var(--d-navy) 100%);
}
.c-cta__btn { box-shadow: 0 8px 24px rgb(0 0 0 / 22%); color: #2b22b0; }
</style>