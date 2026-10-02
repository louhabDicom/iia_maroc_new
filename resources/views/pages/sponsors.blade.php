@extends('layouts.app')

@section('title', __('sponsoring.title'))

@section('content')
    <x-page-hero
        :title="__('sponsoring.title')"
        :crumbs="[__('nav.sponsors') => null]"
        image="assets/images/bg/about_page_bg.jpg" />

    {{-- The offer, in the organisers' own words.

         Three plates lifted from the ARABCIA sponsorship pack: the package
         overview, the activations that sit alongside it, and the implementation
         timeline. They are the actual document the deck carries, so a prospect
         is reading the same figures the committee quotes — rather than a
         paraphrase typed into a template that drifts out of step with it.

         Each is a button to the full-size file rather than an inline image: a
         2133px-wide graphic scaled into a 700px column is unreadable, and
         opening the original is what makes it useful. --}}
    @php
        $sponsorPlates = array_values(array_filter([
            [
                'file'    => 'assets/images/conference/sponsorship-packages-overview.png',
                'title'   => __('sponsoring.plate_packages'),
                'lede'    => __('sponsoring.plate_packages_lede'),
                'width'   => 2133,
                'height'  => 1224,
            ],
            [
                'file'    => 'assets/images/conference/sponsorship-custom-activations.png',
                'title'   => __('sponsoring.plate_activations'),
                'lede'    => __('sponsoring.plate_activations_lede'),
                'width'   => 2133,
                'height'  => 921,
            ],
            [
                'file'    => 'assets/images/conference/sponsorship-timeline.png',
                'title'   => __('sponsoring.plate_timeline'),
                'lede'    => __('sponsoring.plate_timeline_lede'),
                'width'   => 2138,
                'height'  => 442,
            ],
        ], static fn (array $plate): bool => file_exists(public_path($plate['file']))));
    @endphp

    @if ($sponsorPlates !== [])
        <section class="ux-section section-padding-03" aria-labelledby="sponsor-plates-heading">
            <div class="container">

                <x-section-head
                    id="sponsor-plates-heading"
                    :eyebrow="__('sponsoring.title')"
                    :title="__('sponsoring.plates_title')"
                    :lede="__('sponsoring.plates_lede')"
                    :level="2"
                    align="center"
                    class="mb-5" />

                <div class="row g-4" data-ux-stagger="90">
                    @foreach ($sponsorPlates as $plate)
                        <div class="col-lg-4 col-md-6 ux-reveal">
                            <figure class="ux-plate h-100">
                                <a href="{{ asset($plate['file']) }}"
                                   class="ux-plate__media"
                                   data-lightbox="sponsoring"
                                   data-caption="{{ $plate['title'] }}">
                                    <img src="{{ asset($plate['file']) }}"
                                         alt="{{ $plate['title'] }}"
                                         width="{{ $plate['width'] }}"
                                         height="{{ $plate['height'] }}"
                                         loading="lazy"
                                         decoding="async">
                                    <span class="ux-plate__zoom" aria-hidden="true">
                                        <i class="fas fa-up-right-from-square"></i>
                                    </span>
                                </a>

                                <figcaption class="ux-plate__body">
                                    <h3 class="ux-plate__title">{{ $plate['title'] }}</h3>
                                    <p class="ux-plate__lede">{{ $plate['lede'] }}</p>
                                </figcaption>
                            </figure>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif


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
