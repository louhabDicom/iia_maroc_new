@extends('layouts.app')

@section('title', __('programme.title'))
@section('description', __('programme.provisional_notice'))

@section('content')

<div class="d-page d-page--programme">

    {{-- The shared front-office hero: the landing page's own band. See
         components/front/hero.blade.php for why every public page uses one. --}}
    <x-front.hero
        :title="__('programme.title')"
        :eyebrow="$edition->identityLabel()"
        :lede="__('programme.hero_lede')"
        :crumbs="[__('nav.programme') => null]"
        :facts="[
            ['icon' => 'fa-calendar-days', 'label' => $edition->dateLine($locale)],
            ['icon' => 'fa-location-dot',  'label' => $edition->venueLine($locale)],
        ]"
        :cta-label="$edition->registration_open ? __('nav.registration') : null"
        :cta-url="$edition->registration_open ? (auth()->check() ? route('pricing') : route('register')) : null"
        :secondary-label="__('speakers.title')"
        :secondary-url="route('speakers')"
        image="assets/images/bg/hero_bg2.jpg" />

    {{-- The theme of the edition, quoted from the organisers' own presentation
         document rather than paraphrased here.

         It sits directly under the hero because it is the one line a delegate
         quotes back to a colleague, and a programme page that buries its own
         theme has thrown away the most useful thing on it. The "why" text and
         the format grid share the band because they are all answers to the same
         question — what is this event for, and what will actually happen in the
         room — and splitting them would make the page three paragraphs long
         before a single time is shown. --}}
    <section class="d-theme" aria-labelledby="theme-heading">
        <div class="container">
            <div class="d-theme__inner">

                <p class="d-theme__label">
                    <span class="d-theme__rule" aria-hidden="true"></span>
                    @lang('programme.theme_label')
                </p>

                <h2 id="theme-heading" class="d-theme__title">@lang('programme.theme')</h2>

                <div class="d-theme__body">
                    <div class="d-theme__intro">
                        <h3 class="d-theme__subtitle">@lang('programme.why_title')</h3>
                        <p class="d-theme__lede">@lang('programme.why_lede')</p>
                    </div>

                    <div class="d-theme__columns">
                        <p>@lang('programme.why_body_1')</p>
                        <p>@lang('programme.why_body_2')</p>
                    </div>
                </div>

                <h3 class="d-format__heading">@lang('programme.format_title')</h3>
                <p class="d-format__lede">@lang('programme.format_lede')</p>

                <ul class="d-format__grid list-unstyled">
                    @foreach ([
                        ['fa-microphone-lines', 'programme.format_plenaries', 'programme.format_plenaries_text'],
                        ['fa-comments', 'programme.format_panels', 'programme.format_panels_text'],
                        ['fa-people-group', 'programme.format_workshops', 'programme.format_workshops_text'],
                        ['fa-flask-vial', 'programme.format_lab', 'programme.format_lab_text'],
                    ] as $format)
                        <li class="d-format__item">
                            <span class="d-format__icon" aria-hidden="true">
                                <i class="fas {{ $format[0] }}"></i>
                            </span>
                            <h4 class="d-format__title">{{ __($format[1]) }}</h4>
                            <p class="d-format__text">{{ __($format[2]) }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <section class="d-section d-section--alt" aria-labelledby="schedule-heading">
        <div class="container">

            <x-design.section-head
                id="schedule-heading"
                :eyebrow="__('nav.programme')"
                :title="__('programme.schedule_title')"
                :lede="__('programme.schedule_lede')" />

            {{-- The brief states the scientific programme is provisional, so the
                 page says so rather than implying it is final. It sits above the
                 day tabs because it is a caveat about every day, not about one. --}}
            <p class="d-note">
                <i class="fas fa-circle-info" aria-hidden="true"></i>
                <span>@lang('programme.notice')</span>
            </p>

            {{-- Day switcher. Real links rather than tabs, because the selected day
                 is in the query string: a day is then a bookmarkable URL, it can be
                 shared, and the back button behaves. A JS tab would put the state
                 in a variable, so two different days would be one URL and the page
                 would be unlinkable. --}}
            @if (count($days) > 1)
                <nav class="mt-5" aria-label="{{ __('programme.all_days') }}">
                    <ul class="d-tabs">
                        @foreach ($days as $day)
                            <li>
                                <a href="{{ route('programme', array_filter([
                                        'locale' => request()->route('locale'),
                                        'day' => $day->toDateString(),
                                    ])) }}"
                                   class="d-tabs__btn"
                                   @if ($selectedDay?->isSameDay($day)) aria-current="true" @endif>
                                    {{ $day->translatedFormat('l d F') }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            @if ($slots === [])
                <div class="d-empty">
                    <i class="fas fa-calendar-xmark d-empty__icon" aria-hidden="true"></i>
                    <p>@lang('programme.no_sessions')</p>
                </div>
            @else
                {{-- One row per time slot, with parallel sessions side by side. A
                     single flattened list was the 2024 behaviour and it made a
                     double-booked room impossible to spot. --}}
                <ol class="d-timeline list-unstyled">
                    @foreach ($slots as $date => $times)
                        @foreach ($times as $start => $slotSessions)
                            {{-- The slot's end is the latest end in the slot rather than
                                 the end of the first session: parallel sessions are not
                                 required to be the same length, and printing the earlier
                                 one would understate how long the slot runs. --}}
                            @php
                                $slotEnd = collect($slotSessions)
                                    ->max(fn ($session) => $session->endsAtDateTime()->getTimestamp());
                            @endphp
                            <li>
                                <article class="d-timeline__row ux-reveal">
                                    <p class="d-timeline__time" dir="ltr">
                                        {{ $start }}<br>
                                        <span>&ndash; {{ \Illuminate\Support\Carbon::createFromTimestamp($slotEnd)->format('H:i') }}</span>
                                    </p>

                                    <div class="d-timeline__body">
                                        <div class="d-timeline__body-inner d-timeline__sessions">
                                            @foreach ($slotSessions as $session)
                                                <article class="d-card d-card--hover d-session">
                                                    <p class="d-timeline__meta">
                                                        <span>
                                                            <i class="fas fa-tag" aria-hidden="true"></i>
                                                            {{ $session->format->label($locale->value) }}
                                                        </span>

                                                        @if ($session->room)
                                                            <span>
                                                                <i class="fas fa-door-open" aria-hidden="true"></i>
                                                                {{ $session->room->name }}
                                                            </span>
                                                        @endif
                                                    </p>

                                                    <h3 class="d-session__title">{{ $session->title }}</h3>

                                                    @if ($session->track)
                                                        <p class="d-session__track">{{ $session->track->name }}</p>
                                                    @endif

                                                    @if ($session->summary)
                                                        <p class="d-session__summary">{{ $session->summary }}</p>
                                                    @endif

                                                    @if ($session->speakers->isNotEmpty())
                                                        <p class="d-session__speakers">
                                                            {{ $session->speakers->map(fn ($speaker) => $speaker->fullName())->join('، ') }}
                                                        </p>
                                                    @endif

                                                    {{-- durationMinutes() reads the stored times
                                                         rather than assuming a slot length,
                                                         so a session that over-runs is
                                                         visible here. --}}
                                                    <p class="d-session__foot d-timeline__meta" dir="ltr">
                                                        <span>
                                                            {{ $session->startsAtString() }} &ndash; {{ $session->endsAtString() }}
                                                            ({{ $session->durationMinutes() }}&prime;)
                                                        </span>
                                                    </p>
                                                </article>
                                            @endforeach
                                        </div>
                                    </div>
                                </article>
                            </li>
                        @endforeach
                    @endforeach
                </ol>
            @endif
        </div>
    </section>

    {{-- The three workshop tracks, after the schedule rather than inside it.

         A track is a choice made before the day starts, not a session, so it is
         listed as its own section: a delegate picking "Résilience" needs to see
         what that means before they read a timetable, not halfway down it. --}}
    <section class="d-section" aria-labelledby="tracks-heading">
        <div class="container">

            <x-design.section-head
                id="tracks-heading"
                :eyebrow="__('programme.format_workshops')"
                :title="__('programme.tracks_title')"
                :lede="__('programme.tracks_lede')" />

            <ul class="d-tracks list-unstyled" data-ux-stagger="90">
                @foreach ([
                    ['fa-microchip', 'programme.track_ai_title', 'programme.track_ai_text'],
                    ['fa-shield-halved', 'programme.track_resilience_title', 'programme.track_resilience_text'],
                    ['fa-user-graduate', 'programme.track_auditor_title', 'programme.track_auditor_text'],
                ] as $index => $track)
                    <li class="d-track ux-reveal">
                        {{-- dir="ltr": the ordinal is a number, and a number is not
                             reordered by the bidi algorithm even in an RTL page —
                             but keeping it in its own LTR run stops the label from
                             drifting away from it. --}}
                        <p class="d-track__num" dir="ltr">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</p>

                        <div class="d-track__body">
                            <h3 class="d-track__title">
                                <i class="fas {{ $track[0] }}" aria-hidden="true"></i>
                                {{ __($track[1]) }}
                            </h3>
                            <p class="d-track__text">{{ __($track[2]) }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- The Innovation Lab.

         Drawn as one panel rather than three cards because it is a single
         two-session format, and a three-card treatment would imply three
         separate things to choose between. --}}
    <section class="d-section d-section--alt" aria-labelledby="lab-heading">
        <div class="container">
            <div class="d-lab">
                <div class="d-lab__main">
                    <p class="d-lab__badge">
                        <i class="fas fa-flask-vial" aria-hidden="true"></i>
                        @lang('programme.format_lab')
                    </p>

                    <h2 id="lab-heading" class="d-lab__title">@lang('programme.lab_title')</h2>

                    <p class="d-lab__lede">@lang('programme.lab_lede')</p>
                </div>

                <ul class="d-lab__days list-unstyled">
                    <li class="d-lab__day">
                        <i class="fas fa-circle-check" aria-hidden="true"></i>
                        <span>@lang('programme.lab_day1')</span>
                    </li>
                    <li class="d-lab__day">
                        <i class="fas fa-circle-check" aria-hidden="true"></i>
                        <span>@lang('programme.lab_day2')</span>
                    </li>
                </ul>

                <p class="d-lab__note">
                    <i class="fas fa-circle-info" aria-hidden="true"></i>
                    <span>@lang('programme.lab_note')</span>
                </p>
            </div>
        </div>
    </section>

    {{-- The programme as the organisers published it.

         The two day sheets are the client's own artwork, drawn to a schedule that
         is still provisional. Showing them is a promise the database has to keep
         up with, so they are presented as the published programme with the
         provisional note repeated rather than as the authoritative one — and they
         open full-size, because a 2571px-wide sheet scaled into a phone column
         is unreadable and is the single most useful thing on the page to have. --}}
    @php
        $daySheets = array_values(array_filter([
            [
                'file'  => 'assets/images/conference/programme-day-1.png',
                'label' => $days[0]?->translatedFormat('l j F') ?? __('programme.day', ['day' => 1]),
            ],
            [
                'file'  => 'assets/images/conference/programme-day-2.png',
                'label' => $days[1]?->translatedFormat('l j F') ?? __('programme.day', ['day' => 2]),
            ],
        ], static fn (array $sheet): bool => file_exists(public_path($sheet['file']))));
    @endphp

    @if ($daySheets !== [])
        <section class="d-section" aria-labelledby="sheets-heading">
            <div class="container">

                <x-design.section-head
                    id="sheets-heading"
                    :eyebrow="__('programme.title')"
                    :title="__('programme.sheets_title')"
                    :lede="__('programme.sheets_lede')"
                    align="center" />

                <div class="d-cards" data-ux-stagger="90">
                    @foreach ($daySheets as $index => $sheet)
                        <figure class="d-sheet ux-reveal">
                            <a href="{{ asset($sheet['file']) }}"
                               class="d-sheet__media"
                               data-lightbox="programme"
                               data-caption="{{ $sheet['label'] }}">
                                <img src="{{ asset($sheet['file']) }}"
                                     alt="{{ __('programme.sheets_alt', ['day' => $index + 1]) }}"
                                     loading="lazy"
                                     decoding="async">
                                <span class="d-sheet__zoom" aria-hidden="true">
                                    <i class="fas fa-up-right-from-square"></i>
                                </span>
                            </a>
                            <figcaption class="d-sheet__caption">
                                <span class="d-tag">{{ $sheet['label'] }}</span>
                                <span class="d-sheet__hint">@lang('programme.sheets_open')</span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-design.cta-band
        :title="__('programme.cta_title')"
        :text="__('programme.cta_text')"
        :primary-label="$edition->registration_open ? __('pricing.register') : null"
        :primary-url="$edition->registration_open ? (auth()->check() ? route('pricing') : route('register')) : null"
        :secondary-label="__('nav.contact')"
        :secondary-url="route('contact')" />

</div>

@endsection
