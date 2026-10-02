@extends('layouts.app')

@section('title', __('pricing.title'))
@section('description', __('pricing.currency_note'))

@section('content')

    <x-page-hero
        :title="__('pricing.title')"
        :crumbs="[__('nav.pricing') => null]"
        image="assets/images/bg/price_bg.jpg" />


@section('content')
<x-page-hero
        :title="__('pricing.title')"
        :eyebrow="$edition->identityLabel()"
        :lede="__('pricing.currency_note')"
        :crumbs="[__('nav.pricing') => null]"
        :facts="[
            ['icon' => 'fa-calendar-days', 'label' => $edition->dateLine($locale)],
            ['icon' => 'fa-location-dot',  'label' => $edition->venueLine($locale)],
        ]"
        image="assets/images/bg/price_bg.jpg" />

    <section class="ux-section section-padding-03 ux-section--defer"
             aria-labelledby="pricing-heading">
        <div class="container">

            {{-- The states that gate ordering, stated above the cards rather than
                 discovered at the checkout. These are notices rather than alerts:
                 none of them is an error, and an error box would say otherwise. --}}
            <div class="d-flex flex-column gap-3 mb-5">
                @unless ($registrationOpen)
                    <div class="ux-notice ux-notice--warning ux-reveal" role="status">
                        <i class="fas fa-lock ux-notice__icon" aria-hidden="true"></i>
                        <span>@lang('pricing.closed')</span>
                    </div>
                @endunless

                @auth
                    @unless ($canOrder)
                        <div class="ux-notice ux-notice--warning ux-reveal" role="status">
                            <i class="fas fa-circle-exclamation ux-notice__icon" aria-hidden="true"></i>

                            {{-- Told what to do about it here, with a link, rather than
                                 left to be discovered when the form refuses. --}}
                            <span>
                                @if (auth()->user()->hasVerifiedPhone())
                                    @lang('register.terms_required')
                                @else
                                    <a href="{{ route('verification.notice') }}" class="app-link">
                                        @lang('verify.title')
                                    </a>
                                @endif
                            </span>
                        </div>
                    @endunless
                @endauth

                {{-- The membership notice from the 2024 page, kept above the cards.
                     It is the single most useful line here for most visitors: most
                     of them are already members and are buying at the member rate. --}}
@php
                            // Which price applies is decided by the server from the
                            // Membership record, never from anything the visitor chose.
                            // Showing the member rate to an active member is a
                            // convenience; it grants nothing.
                            $amount = $type->priceFor($isMember);
                            $includes = (array) ($type->includes ?: []);

                            // Only flag a recommended tier once there is a genuine
                            // choice. With one or two types, flagging the first would
                            // be arbitrary dressing.
                            $featured = $ticketTypes->count() >= 3 && $index === 0;
                        @endphp

                        <div class="col-lg-6 col-12">
                            <article @class(['ux-price', 'ux-price--featured' => $featured])>

                                @if ($featured)
                                    <span class="ux-price__flag">@lang('pricing.recommended')</span>
                                @endif

                                <h2 class="ux-price__name">{{ $type->name }}</h2>

                                <p class="ux-price__audience">
                                    @lang($isMember ? 'pricing.member' : 'pricing.standard')
                                </p>

                                {{-- dir="ltr" so the amount is not bidi-reordered next to
                                     a currency symbol on the Arabic page. --}}
                                <span class="ux-price__amount" dir="ltr">
                                    {{ $type->formatAmount($amount) }}
                                </span>

                                <p class="ux-price__note">@lang('pricing.per_person')</p>

                                @if ($type->description)
                                    <p class="text-muted mb-3">{{ $type->description }}</p>
                                @endif

                                @if ($includes !== [])
                                    <p class="fw-bold mt-3 mb-2">@lang('pricing.includes')</p>

                                    <ul class="ux-checks text-start">
                                        @foreach ($includes as $item)
                                            <li class="ux-checks__item">{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                <div class="ux-price__foot">
                                    @if (! $registrationOpen)
                                        {{-- A real disabled button rather than a styled
                                             <span>: it looks identical, and a screen
                                             reader still reports it as unavailable. --}}
                                        <button type="button" class="ux-btn ux-btn--ghost w-100" disabled>
                                            <span>@lang('pricing.closed')</span>
                                        </button>
                                    @else
                                        {{-- PLACEHOLDER_FORM --}}
                                    @endif
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <x-cta-band
        :title="__('pricing.register')"
        :text="__('login.member_notice')"
        :primary-label="__('pricing.register')"
        :primary-url="auth()->check() ? route('checkout') : route('register')"
        :secondary-label="__('nav.contact')"
        :secondary-url="route('contact')" />

@endsection
                <div class="ux-notice ux-reveal" role="note">
                    <i class="fas fa-id-badge ux-notice__icon" aria-hidden="true"></i>
                    <span>@lang('login.member_notice')</span>
                </div>
            </div>

            @if ($ticketTypes->isEmpty())
                <x-empty-state :message="__('state.empty')" icon="fas fa-tags" />
            @else
                <div class="row g-4 justify-content-center" data-ux-stagger="90">
                    @foreach ($ticketTypes as $index => $type)
