@extends('layouts.app')

@section('title', __('programme.title'))
@section('description', __('programme.provisional_notice'))

@section('content')

    <x-page-hero
        :title="__('programme.title')"
        :crumbs="[__('nav.programme') => null]" />

    <div class="schedule-area section-padding-02 grey-bg">
        <img src="{{ asset('assets/images/shape/schedule_shape1.png') }}" class="schedule-shape1" alt="">
        <img src="{{ asset('assets/images/shape/schedule_shape2.png') }}" class="schedule-shape2" alt="">

        <div class="container">
            {{-- The brief states the scientific programme is provisional, so the page
                 says so rather than implying it is final. --}}
            <p class="notice-provisional">@lang('programme.provisional_notice')</p>

            {{-- Day switcher. Real links rather than tabs, because the selected day
                 is in the query string: a day is then a bookmarkable URL, it can be
                 shared, and the back button behaves. Bootstrap pills would put the
                 state in a JS variable, so two different days would be one URL and
                 the page would be unlinkable. --}}
            @if (count($days) > 1)
                <nav class="programme-day-nav nav d-block justify-content-center mt-4"
                     aria-label="{{ __('programme.all_days') }}">
                    @foreach ($days as $day)
                        <a href="{{ route('programme', array_filter([
                                'locale' => request()->route('locale'),
                                'day' => $day->toDateString(),
                            ])) }}"
                           @class([
                               'nav-link',
                               'active' => $selectedDay?->isSameDay($day),
                           ])
                           @if ($selectedDay?->isSameDay($day)) aria-current="page" @endif>
                            {{ $day->translatedFormat('l d F') }}
                        </a>
                    @endforeach
                </nav>
            @endif

            @if ($slots === [])
                <p class="empty-state mt-4">@lang('programme.no_sessions')</p>
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
                        <div class="schedule-row">
                            <div class="row g-0 align-items-stretch">
                                <div class="col-lg-3 col-12">
                                    <div class="schedule-time" dir="ltr">
                                        {{ $start }}<br>
                                        <span class="schedule-card__meta">
                                            &ndash; {{ \Illuminate\Support\Carbon::createFromTimestamp($slotEnd)->format('H:i') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-lg-9 col-12">
                                    <div class="row g-3">
                                        @foreach ($slotSessions as $session)
                                            <div class="col-md-6 col-12">
                                                <article class="schedule-card">
                                                    <p class="schedule-card__meta">
                                                        {{ $session->format->label($locale->value) }}
                                                        @if ($session->room)
                                                            &middot; {{ $session->room->name }}
                                                        @endif
                                                    </p>

                                                    <h2 class="schedule-card__title">{{ $session->title }}</h2>

                                                    @if ($session->track)
                                                        <p class="schedule-card__track">{{ $session->track->name }}</p>
                                                    @endif

                                                    @if ($session->summary)
                                                        <p class="schedule-card__track">{{ $session->summary }}</p>
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
                                                    <p class="schedule-card__meta mt-2" dir="ltr">
                                                        {{ $session->startsAtString() }} &ndash; {{ $session->endsAtString() }}
                                                        ({{ $session->durationMinutes() }}&prime;)
                                                    </p>
                                                </article>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

@endsection
