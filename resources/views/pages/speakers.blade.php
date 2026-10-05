@extends('layouts.app')

@section('title', __('speakers.title'))
@section('description', $edition->introduction)

@section('content')

<div class="d-page d-page--speakers">

    <x-front.hero
        :title="__('speakers.title')"
        :eyebrow="$edition->identityLabel()"
        :lede="__('speakers.hero_lede')"
        :crumbs="[__('nav.speakers') => null]"
        :facts="[
            ['icon' => 'fa-calendar-alt', 'label' => $edition->dateLine($locale)],
            ['icon' => 'fa-map-marker-alt',  'label' => $edition->venueLine($locale)],
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
        <section class="d-section">
            <div class="container">
                <div class="d-empty">
                    <i class="fas fa-user-slash d-empty__icon" aria-hidden="true"></i>
                    <p>@lang('speakers.subtitle')</p>
                </div>
            </div>
        </section>
    @else
        {{-- The keynote band is rendered only when there ARE keynotes.

             The 2024 markup rendered the band unconditionally and put an
             "announced soon" empty state inside it. With a full line-up directly
             below, that read as a failure sitting on top of eighteen working
             cards — two sections of the same page saying contradictory things.
             With no keynotes the band is simply not there, and the line-up is
             the first thing the visitor sees. --}}
        @if ($keynotes->isNotEmpty())
            <section class="d-section d-section--alt" aria-labelledby="keynotes-heading">
                <div class="container">

                    <x-design.section-head
                        id="keynotes-heading"
                        :eyebrow="__('speakers.keynotes')"
                        :title="__('speakers.keynotes')"
                        :lede="__('speakers.keynotes_lede')" />

                    {{-- The two line-ups are separate lists rather than one list with
                         a heading injected, so a screen reader can count each. --}}
                    <ul class="d-speakers d-speakers--wide" data-ux-stagger="90">
                        @foreach ($keynotes as $speaker)
                            <x-design.speaker-card :speaker="$speaker" variant="wide" class="ux-reveal" />
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif

        @if ($speakers->isNotEmpty())
            <section @class([
                'd-section',
                // The tint is only a separator when there is something above it.
                'd-section--alt' => $keynotes->isEmpty(),
            ])
                     aria-labelledby="speakers-heading">
                <div class="container">

                    <x-design.section-head
                        id="speakers-heading"
                        :eyebrow="__('speakers.title')"
                        :title="__('speakers.lineup_title')"
                        :lede="__('speakers.lineup_lede')"
                        align="center" />

                    {{-- `data-ux-stagger` delays each card's reveal by a few
                         hundredths so the grid arrives as a wave rather than as
                         one block appearing at once. --}}
                    <ul class="d-speakers" data-ux-stagger="70">
                        @foreach ($speakers as $speaker)
                            <x-design.speaker-card :speaker="$speaker" variant="grid" class="ux-reveal" />
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif

        <x-design.cta-band
            :title="__('programme.title')"
            :text="__('programme.provisional_notice')"
            :primary-label="__('programme.title')"
            :primary-url="route('programme')"
            :secondary-label="__('nav.contact')"
            :secondary-url="route('contact')" />
    @endif

</div>

@endsection
