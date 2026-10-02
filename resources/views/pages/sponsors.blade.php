@extends('layouts.app')

@section('title', __('sponsoring.title'))

@section('content')
    <x-page-hero
        :title="__('sponsoring.title')"
        :crumbs="[__('nav.sponsors') => null]"
        image="assets/images/bg/about_page_bg.jpg" />

    <div class="section-padding-04">
        <div class="container">
            <div class="app-shell app-shell--wide">
            <!--BODY-->
            {{-- Every tier in the catalogue gets a section, including the empty
                 ones. A gold section with nothing in it reads as "gold
                 sponsorship is still available", which is the reason for showing
                 it; hiding the tier would just make the offer invisible. --}}
            @foreach ($tierOrder as $tier => $rank)
                @php
                    $inTier = $tiers[$tier] ?? collect();
                    $content = $tierContent->get($tier);
                @endphp

                {{-- Every tier gets a section, including the empty ones. A gold
                     section with nothing in it reads as "gold sponsorship is
                     still available", which is the reason for showing it;
                     hiding the tier would just make the offer invisible. --}}
                <section class="app-section" aria-labelledby="tier-{{ $tier }}">
                    <h2 id="tier-{{ $tier }}" class="app-section__title">
                        {{ __("sponsoring.tier.{$tier}") }}
                    </h2>

                    @if ($content?->text())
                        <p class="app-note">{{ $content->text() }}</p>
                    @endif

                    @if ($inTier->isEmpty())
                        <p class="app-empty mt-3">@lang('sponsoring.no_sponsors')</p>
                    @else
                        {{-- Logos in a card grid. A flat row of eight logos with
                             no separation reads as one block of colour, and the
                             partner's name becomes unreadable at any size. --}}
                        <ul class="app-logo-grid mt-4">
                            @foreach ($inTier as $sponsor)
                                <li class="app-logo">
                                    @if ($sponsor->website_url)
                                        <a href="{{ $sponsor->website_url }}"
                                           rel="noopener noreferrer sponsored"
                                           target="_blank">
                                            <x-sponsor-logo :sponsor="$sponsor" />
                                        </a>
                                    @else
                                        <x-sponsor-logo :sponsor="$sponsor" />
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach

            @if ($organisations->isNotEmpty())
                <section class="app-section" aria-labelledby="sponsor-organisations">
                    <h2 id="sponsor-organisations" class="app-section__title">
                        @lang('home.sponsors_title')
                    </h2>

                    <ul class="app-list">
                        @foreach ($organisations as $organisation)
                            <li class="app-list__row">
                                <div>
                                    <p class="app-note--strong">{{ $organisation->name }}</p>
                                    @if ($organisation->role)
                                        <p class="app-note">{{ $organisation->role }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($previousSponsors->isNotEmpty())
                <section class="app-section" aria-labelledby="sponsor-previous">
                    <h2 id="sponsor-previous" class="app-section__title">@lang('sponsoring.previous')</h2>

                    <ul class="app-logo-grid mt-4">
                        @foreach ($previousSponsors as $sponsor)
                            <li class="app-logo">
                                @if ($sponsor->website_url)
                                    <a href="{{ $sponsor->website_url }}"
                                       rel="noopener noreferrer sponsored"
                                       target="_blank">
                                        <x-sponsor-logo :sponsor="$sponsor" />
                                    </a>
                                @else
                                    <x-sponsor-logo :sponsor="$sponsor" />
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <p class="mt-5 text-center">
                <a href="{{ route('contact') }}" class="btn-join">@lang('sponsoring.become_partner')</a>
            </p>
            </div>
        </div>
    </div>
@endsection
