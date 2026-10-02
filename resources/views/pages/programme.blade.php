@extends('layouts.app')

@section('title', __('programme.title'))
@section('description', __('programme.provisional_notice'))

@section('content')

    <x-page-hero
        :title="__('programme.title')"
        :crumbs="[__('nav.programme') => null]" />

    <div class="schedule-area section-padding-02" style="background:var(--ux-surface-alt);">

        <div class="container">
            {{-- The brief states the scientific programme is provisional, so the page
                 says so rather than implying it is final. --}}
            <p class="notice-provisional text-center ux-reveal">@lang('programme.provisional_notice')</p>

            {{-- Day switcher. Real links rather than tabs, because the selected day
                 is in the query string: a day is then a bookmarkable URL, it can be
                 shared, and the back button behaves. Bootstrap pills would put the
                 state in a JS variable, so two different days would be one URL and
                 the page would be unlinkable. --}}
            @if (count($days) > 1)
                <nav class="ux-day-nav mt-4"
                     aria-label="{{ __('programme.all_days') }}">
                    @foreach ($days as $day)
                        <a href="{{ route('programme', array_filter([
                                'locale' => request()->route('locale'),
                                'day' => $day->toDateString(),
                            ])) }}"
                           @class(['ux-day-nav__item', 'is-active' => $selectedDay?->isSameDay($day)])
                           @if ($selectedDay?->isSameDay($day)) aria-current="page" @endif>
                            {{ $day->translatedFormat('l d F') }}
                        </a>
                    @endforeach
                </nav>
            @endif

            @if ($slots === [])
                <p class="app-empty mt-4 ux-reveal">@lang('programme.no_sessions')</p>
            @endif

            {{-- One block per time slot, with parallel sessions side by side. A
                 single flattened list was the 2024 behaviour and it made a
                 double-booked room impossible to spot. --}}
            @foreach ($slots as $date => $times)
                <div class="schedule-wrapper mt-4">
                    @foreach ($times as $start => $slotSessions)
                        {{-- The slot's end is the latest end in the slot rather than
                             the end of the first session: parallel sessions are not
                             required to be the same length, and printing the
                             earlier one would understate how long the slot runs. --}}
                        @php
                            $slotEnd = collect($slotSessions)
                                ->max(fn ($session) => $session->endsAtDateTime()->getTimestamp());
                        @endphp
                        <article class="ux-timeline__slot ux-reveal">
                            <p class="ux-timeline__time" dir="ltr">
                                {{ $start }}<br>
                                <span>&ndash; {{ \Illuminate\Support\Carbon::createFromTimestamp($slotEnd)->format('H:i') }}</span>
                            </p>

                            <div class="ux-timeline__body">
                                <div class="row g-3" data-ux-stagger="70">
                                    @foreach ($slotSessions as $session)
                                        <div class="col-md-6 col-12">
                                            <div class="ux-card ux-card--edge ux-card--lift schedule-card h-100">
                                                <div class="p-4">

                                                    <p class="schedule-card__meta">
                                                        <span class="ux-tag">{{ $session->format->label($locale->value) }}</span>
                                                        @if ($session->room)
                                                            <span class="ux-tag ux-tag--muted">{{ $session->room->name }}</span>
                                                        @endif
                                                    </p>

                                                    <h2 class="schedule-card__title">{{ $session->title }}</h2>

                                                    @if ($session->track)
                                                        <p class="schedule-card__track">{{ $session->track->name }}</p>
                                                    @endif

                                                    @if ($session->summary)
                                                        <p class="schedule-card__summary">{{ $session->summary }}</p>
                                                    @endif

                                                    @if ($session->speakers->isNotEmpty())
                                                        <p class="schedule-card__speakers">
                                                            {{ $session->speakers->map(fn ($speaker) => $speaker->fullName())->join(', ') }}
                                                        </p>
                                                    @endif

                                                    {{-- durationMinutes() reads the stored times
                                                         rather than assuming a slot length,
                                                         so a session that over-runs is
                                                         visible here. --}}
                                                    <p class="schedule-card__meta mt-3 mb-0" dir="ltr">
                                                        {{ $session->startsAtString() }} &ndash; {{ $session->endsAtString() }}
                                                        ({{ $session->durationMinutes() }}&prime;)
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endforeach
        </div>

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
        <section class="ux-section section-padding-03" aria-labelledby="sheets-heading">
            <div class="container">

                <x-section-head
                    id="sheets-heading"
                    :eyebrow="__('programme.title')"
                    :title="__('programme.sheets_title')"
                    :lede="__('programme.provisional_notice')"
                    :level="2"
                    align="center"
                    class="mb-5" />

                <div class="row g-4" data-ux-stagger="90">
                    @foreach ($daySheets as $index => $sheet)
                        <div class="col-md-6 ux-reveal">
                            <figure class="ux-sheet h-100">
                                <a href="{{ asset($sheet['file']) }}"
                                   class="ux-sheet__media"
                                   data-lightbox="programme"
                                   data-caption="{{ $sheet['label'] }}">
                                    <img src="{{ asset($sheet['file']) }}"
                                         alt="{{ __('programme.sheets_alt', ['day' => $index + 1]) }}"
                                         loading="lazy"
                                         decoding="async">
                                    <span class="ux-sheet__zoom" aria-hidden="true">
                                        <i class="fas fa-up-right-from-square"></i>
                                    </span>
                                </a>
                                <figcaption class="ux-sheet__caption">
                                    <span class="ux-tag ux-tag--solid">{{ $sheet['label'] }}</span>
                                    <span class="ux-sheet__hint">@lang('programme.sheets_open')</span>
                                </figcaption>
                            </figure>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    </div>

@endsection
