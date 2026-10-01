@extends('layouts.app')

@section('title', __('venue.title'))

@section('content')

    <x-page-hero
        :title="__('venue.title')"
        :crumbs="[__('nav.venue') => null]"
        image="assets/images/bg/map_bg.png" />

    <div class="section-padding-04">
        <div class="container">
            <div class="row">
                <div class="col-lg-7 col-12">
                    {{-- All venue data from the edition row. The 2024 build hardcoded
                         "Casablanca" in this template while the venue column held
                         the real address, so the two disagreed on the same page. --}}
                    <div class="section-title">
                        <h2 class="title">@lang('home.venue_title')</h2>
                    </div>

                    <p class="fs-5 mt-3">
                        {{ $edition->dateLine($locale) }}<br>
                        {{ $edition->venueLine($locale) }}
                    </p>

                    @if ($edition->venue_address)
                        <address class="mt-3">
                            {!! nl2br(e($edition->venue_address)) !!}
                        </address>
                    @endif

                    @if ($edition->venue_map_url)
                        <p class="mt-4">
                            <a href="{{ $edition->venue_map_url }}"
                               rel="noopener noreferrer nofollow"
                               target="_blank"
                               class="btn">@lang('venue.map')</a>
                        </p>
                    @endif
                </div>

                <div class="col-lg-5 col-12">
                    <img src="{{ asset('assets/images/about_page_img.jpg') }}"
                         alt="{{ $edition->venueLine($locale) }}"
                         class="img-fluid w-100">
                </div>
            </div>

            {{-- Only rendered when rooms exist. --}}
            @if ($rooms->isNotEmpty())
                <section class="mt-5" aria-labelledby="rooms-heading">
                    <div class="section-title text-center">
                        <h2 class="title" id="rooms-heading">@lang('programme.room')</h2>
                    </div>

                    <ul class="list-unstyled mt-3">
                        @foreach ($rooms as $room)
                            <li class="room-row">
                                <span class="room-row__name">{{ $room->name }}</span>
                                <span class="room-row__meta">
                                    @if ($room->capacity)
                                        {{ trans_choice('venue.capacity', $room->capacity, ['count' => $room->capacity]) }}
                                    @endif
                                    @if (! is_null($room->floor))
                                        {{-- The level is stored as a number, so the wording is
                                             translated here rather than baked into the row. --}}
                                        <span class="ms-3">{{ __('venue.floor', ['level' => $room->floor]) }}</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>

@endsection
