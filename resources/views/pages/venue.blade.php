@extends('layouts.app')

@section('title', __('venue.title'))
@section('description', $edition->introduction)

@section('content')

    {{-- The venue page is the page a delegate opens on a phone, standing at a
         taxi rank, so "when, where, and how do I get there" is answered in the
         hero band itself rather than after a scroll. --}}
    <x-page-hero
        :title="__('venue.title')"
        :eyebrow="$edition->identityLabel()"
        :lede="__('venue.venue_lede', [
            'venue' => $edition->venue_name,
            'city' => $edition->city,
        ])"
        :crumbs="[__('nav.venue') => null]"
        :facts="[
            ['icon' => 'fa-calendar-days', 'label' => $edition->dateLine($locale)],
            ['icon' => 'fa-location-dot',  'label' => $edition->venueLine($locale)],
        ]"
        :cta-label="$edition->venue_map_url ? __('venue.map_cta') : null"
        :cta-url="$edition->venue_map_url"
        :cta-url-external="(bool) $edition->venue_map_url"
        :secondary-label="__('programme.title')"
        :secondary-url="route('programme')"
        image="assets/images/bg/map_bg.png" />

    {{-- Venue details and map. --}}
    <section class="ux-section section-padding-03 ux-section--defer"
             aria-labelledby="venue-heading">
        <div class="container">
            <div class="row g-5 align-items-center">

                <div class="col-lg-6 ux-reveal ux-reveal-left">
                    <x-section-head
                        id="venue-heading"
                        :eyebrow="__('venue.venue_name')"
                        :title="$edition->venue_name"
                        :level="2"
                        :lede="$edition->venueLine($locale)" />

                    {{-- An <address> element, so a screen reader announces this as a
                         location rather than as three unrelated strings. The
                         attribution it implies is correct: this is the venue's own
                         postal address. --}}
                    @if ($edition->venue_address)
                        <address class="mt-4 text-muted fs-5" style="font-style: normal;">
                            {!! nl2br(e($edition->venue_address)) !!}
                        </address>
                    @endif

                    <div class="row g-3 mt-4" data-ux-stagger="80">
                        <div class="col-sm-6">
                            <div class="ux-feature ux-reveal">
                                <span class="ux-feature__icon ux-feature__icon--cool" aria-hidden="true">
                                    <i class="fas fa-calendar-days"></i>
                                </span>
                                <div class="ux-feature__body">
                                    <h3 class="ux-feature__title">{{ $edition->dateLine($locale) }}</h3>
                                    <p class="ux-feature__text">{{ $edition->venueLine($locale) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="ux-feature ux-reveal">
                                <span class="ux-feature__icon ux-feature__icon--gold" aria-hidden="true">
                                    <i class="fas fa-city"></i>
                                </span>
                                <div class="ux-feature__body">
                                    <h3 class="ux-feature__title">{{ $edition->city }}</h3>
                                    <p class="ux-feature__text">{{ $edition->country_iso2 }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- A real telephone link. A delegate reading this on a phone
                         can tap it, which is the entire reason the number is here. --}}
                    @if ($edition->contact_phone)
                        <div class="mt-4">
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $edition->contact_phone) }}"
                               class="ux-btn ux-btn--ghost"
                               dir="ltr">
                                <span>
                                    <i class="fas fa-phone me-2" aria-hidden="true"></i>
                                    {{ $edition->contact_phone }}
                                </span>
                            </a>
                        </div>
                    @endif
                </div>

                <div class="col-lg-6 ux-reveal ux-reveal-right">
                    @if ($edition->venue_map_url)
                        {{-- The map is a link, not an embedded iframe. A third-party
                             iframe costs a cookie banner and several hundred
                             kilobytes before the visitor can read the address they
                             came for, and it leaks the referrer off-site. The link
                             offers the same destination and neither cost. --}}
                        <a href="{{ $edition->venue_map_url }}"
                           rel="noopener noreferrer nofollow"
                           target="_blank"
                           class="d-block position-relative ux-card ux-card--edge ux-card--lift overflow-hidden"
                           aria-label="{{ __('venue.open_map') }}">
                            <img src="{{ asset('assets/images/bg/map_bg.png') }}"
                                 alt="{{ __('venue.map_title') }}"
                                 class="w-100"
                                 width="720"
                                 height="480"
                                 loading="lazy"
                                 decoding="async"
                                 style="aspect-ratio: 3 / 2; object-fit: cover;">

                            {{-- The pin is an overlay rather than a second image: it
                                 has to sit in the same place at every viewport, and
                                 absolutely positioning it is what guarantees that. --}}
                            <span class="position-absolute top-50 start-50 translate-middle"
                                  aria-hidden="true"
                                  style="color: var(--arab-primary); font-size: 2.6rem; filter: drop-shadow(0 4px 8px rgb(0 0 0 / 45%));">
                                <i class="fas fa-location-dot"></i>
                            </span>

                            <span class="ux-btn ux-btn--primary position-absolute bottom-0 start-50 translate-middle"
                                  style="margin-bottom: 1.5rem;">
                                <span>@lang('venue.map_cta')</span>
                            </span>
                        </a>
                    @else
                        {{-- No map configured: say so, rather than showing an empty
                             frame that suggests one was meant to be there. --}}
                        <x-empty-state
                            :message="__('state.not_available')"
                            icon="fas fa-map-location-dot" />
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Rooms. Only rendered when the edition has any: an empty list here tells a
         visitor nothing at all about the venue. --}}
    @if ($rooms->isNotEmpty())
        <section class="ux-section section-padding-03 ux-section--tint ux-section--defer"
                 aria-labelledby="rooms-heading">
            <div class="container">

                <x-section-head
                    id="rooms-heading"
                    :eyebrow="__('programme.room')"
                    :title="__('venue.rooms')"
                    :lede="__('venue.rooms_lede')"
                    :level="2"
                    align="center"
                    class="mb-5" />

                <ul class="row g-3 list-unstyled" data-ux-stagger="60">
                    @foreach ($rooms as $room)
                        <li class="col-sm-6 col-lg-4 ux-reveal">
                            <div class="ux-feature ux-feature--stacked h-100">
                                <span class="ux-feature__icon" aria-hidden="true">
                                    <i class="fas fa-door-open"></i>
                                </span>

                                <div class="ux-feature__body">
                                    <h3 class="ux-feature__title">{{ $room->name }}</h3>

                                    @if ($room->code)
                                        {{-- The code and the name are both shown: the code
                                             is what is printed on the programme, the
                                             name is what staff will say to you. --}}
                                        <p class="mt-2">
                                            <span class="ux-tag ux-tag--muted">{{ $room->code }}</span>
                                        </p>
                                    @endif

                                    @if ($room->capacity || ! is_null($room->floor))
                                        <p class="ux-tags mt-3">
                                            @if ($room->capacity)
                                                <span class="ux-tag">
                                                    <i class="fas fa-users" aria-hidden="true"></i>
                                                    {{ trans_choice('venue.capacity', $room->capacity, ['count' => $room->capacity]) }}
                                                </span>
                                            @endif

                                            @if (! is_null($room->floor))
                                                {{-- The level is stored as a number, so the
                                                     wording is translated here rather
                                                     than baked into the row. --}}
                                                <span class="ux-tag ux-tag--muted">
                                                    <i class="fas fa-layer-group" aria-hidden="true"></i>
                                                    {{ __('venue.floor', ['level' => $room->floor]) }}
                                                </span>
                                            @endif
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- Practical information. The three tiles are built from an array rather than
         written out three times, so adding a fourth (a WhatsApp line, a hotel
         partner) is one entry and not a copy-paste. --}}
    @php
        $contactTiles = array_values(array_filter([
            [
                'icon'  => 'fa-location-dot',
                'label' => __('venue.venue_name'),
                'value' => $edition->venueLine($locale),
                'href'  => null,
            ],
            [
                'icon'  => 'fa-envelope-open-text',
                'label' => __('contact.email'),
                'value' => $edition->contact_email,
                'href'  => $edition->contact_email ? 'mailto:'.$edition->contact_email : null,
            ],
            [
                'icon'  => 'fa-phone',
                'label' => __('contact.phone'),
                'value' => $edition->contact_phone,
                'href'  => $edition->contact_phone
                    ? 'tel:'.preg_replace('/[^0-9+]/', '', $edition->contact_phone)
                    : null,
            ],
        ], fn (array $tile): bool => filled($tile['value'])));
    @endphp

    @if ($contactTiles !== [])
        <section class="ux-section section-padding-03 ux-section--defer"
                 aria-labelledby="practical-heading">
            <div class="container">

                <x-section-head
                    id="practical-heading"
                    :eyebrow="__('venue.practical')"
                    :title="__('venue.practical')"
                    :lede="__('venue.practical_lede')"
                    :level="2"
                    align="center"
                    class="mb-5" />

                <div class="row g-4 justify-content-center" data-ux-stagger="80">
                    @foreach ($contactTiles as $tile)
                        <div class="col-lg-4 col-md-6 ux-reveal">
                            <div class="ux-card ux-card--edge ux-card--lift h-100 p-4">
                                <div class="ux-tile">
                                    <span class="ux-tile__icon" aria-hidden="true">
                                        <i class="fas {{ $tile['icon'] }}"></i>
                                    </span>

                                    <div class="ux-feature__body">
                                        <span class="ux-tile__label">{{ $tile['label'] }}</span>

                                        {{-- `dir="ltr"` on the two machine-formatted
                                             values: an email address and a phone number
                                             are both reordered by the bidi algorithm on
                                             the Arabic page if left to it. --}}
                                        <p class="ux-tile__value"
                                           @if ($tile['href']) dir="ltr" @endif>
                                            @if ($tile['href'])
                                                <a href="{{ $tile['href'] }}">{{ $tile['value'] }}</a>
                                            @else
                                                {{ $tile['value'] }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-cta-band
        :title="__('pricing.title')"
        :text="__('pricing.currency_note')"
        :primary-label="$edition->registration_open ? __('pricing.register') : null"
        :primary-url="$edition->registration_open ? (auth()->check() ? route('pricing') : route('register')) : null"
        :secondary-label="__('nav.contact')"
        :secondary-url="route('contact')" />

@endsection
