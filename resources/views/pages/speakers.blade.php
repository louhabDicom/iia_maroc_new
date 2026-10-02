@extends('layouts.app')

@section('title', __('speakers.title'))
@section('description', $edition->introduction)

@section('content')

    <x-page-hero
        :title="__('speakers.title')"
        :eyebrow="$edition->identityLabel()"
        :lede="$edition->introduction"
        :crumbs="[__('nav.speakers') => null]"
        :facts="[
            ['icon' => 'fa-calendar-days', 'label' => $edition->dateLine($locale)],
            ['icon' => 'fa-location-dot',  'label' => $edition->venueLine($locale)],
        ]"
        :cta-label="$edition->registration_open ? __('nav.registration') : null"
        :cta-url="$edition->registration_open ? (auth()->check() ? route('pricing') : route('register')) : null"
        :secondary-label="__('programme.title')"
        :secondary-url="route('programme')"
        image="assets/images/bg/speaker_bg_h4.jpg" />

    {{-- Stated up front because it is true: the line-up is confirmed
         incrementally. Presenting unconfirmed names as final is how a speaker
         withdraws and the site is left wrong. --}}
    @if ($keynotes->isEmpty() && $speakers->isEmpty())
        <div class="section-padding-04">
            <div class="container">
                <x-empty-state :message="__('speakers.subtitle')" />
            </div>
        </div>
    @else
        {{-- The keynote band is rendered only when there ARE keynotes.

             The 2024 markup rendered the band unconditionally and put an
             "announced soon" empty state inside it. With a full line-up directly
             below, that read as a failure sitting on top of eighteen working
             cards — two sections of the same page saying contradictory things.
             With no keynotes the band is simply not there, and the line-up is
             the first thing the visitor sees. --}}
        @if ($keynotes->isNotEmpty())
            <section class="ux-section section-padding-03 ux-section--defer"
                     aria-labelledby="keynotes-heading">
                <div class="container">

                    <x-section-head
                        id="keynotes-heading"
                        :eyebrow="__('speakers.keynotes')"
                        :title="__('speakers.keynotes')"
                        :level="2"
                        align="center"
                        class="mb-5" />

                    {{-- The two line-ups are separate lists rather than one list with
                         a heading injected, so a screen reader can count each. --}}
                    <ul class="row g-4 list-unstyled" data-ux-stagger="90">
                        @foreach ($keynotes as $speaker)
                            <li class="col-12">
                                <x-speaker-card :speaker="$speaker" variant="wide" />
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif

        @if ($speakers->isNotEmpty())
            <section @class([
                'ux-section section-padding-03 ux-section--defer',
                // The tint is only a separator when there is something above it.
                'ux-section--tint' => $keynotes->isEmpty(),
            ])
                     aria-labelledby="speakers-heading">
                <div class="container">

                    <x-section-head
                        id="speakers-heading"
                        :eyebrow="__('speakers.title')"
                        :title="__('speakers.title')"
                        :level="2"
                        :lede="__('speakers.subtitle')"
                        align="center"
                        class="mb-5" />

                    {{-- `data-ux-stagger` delays each card's reveal by a few
                         hundredths so the grid arrives as a wave rather than as
                         one block appearing at once. --}}
                    <ul class="row g-4 list-unstyled" data-ux-stagger="70">
                        @foreach ($speakers as $speaker)
                            <li class="col-lg-4 col-md-6 col-12 ux-reveal">
                                <x-speaker-card :speaker="$speaker" variant="grid" />
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif

        <x-cta-band
            :title="__('programme.title')"
            :text="__('programme.provisional_notice')"
            :primary-label="__('programme.title')"
            :primary-url="route('programme')"
            :secondary-label="__('nav.contact')"
            :secondary-url="route('contact')" />
    @endif

@endsection
