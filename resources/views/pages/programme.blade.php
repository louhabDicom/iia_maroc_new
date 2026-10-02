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
    </div>

@endsection
