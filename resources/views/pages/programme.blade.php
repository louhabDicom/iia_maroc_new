@extends('layouts.app')

@section('title', __('programme.title'))
@section('description', __('programme.provisional_notice'))

@section('content')

<div class="d-page d-page--programme">

    <x-design.page-hero
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
