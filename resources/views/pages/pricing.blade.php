@extends('layouts.app')

@section('title', __('pricing.title'))
@section('description', __('pricing.currency_note'))

@section('content')

    <x-page-hero
        :title="__('pricing.title')"
        :crumbs="[__('nav.pricing') => null]"
        image="assets/images/bg/price_bg.jpg" />

    <div class="section-padding-04">
        <div class="container">
            <p class="text-center">@lang('pricing.currency_note')</p>

            @unless ($registrationOpen)
                <p class="notice-provisional text-center">@lang('pricing.closed')</p>
            @endunless

            {{-- An unverified visitor is told what to do about it here rather than
                 discovering it at the checkout. --}}
            @auth
                @unless ($canOrder)
                    <p class="notice-provisional text-center">
                        @if (auth()->user()->hasVerifiedPhone())
                            @lang('register.terms_required')
                        @else
                            <a href="{{ route('verification.notice') }}">@lang('verify.title')</a>
                        @endif
                    </p>
                @endunless
            @endauth

            @if ($ticketTypes->isEmpty())
                <p class="empty-state mt-4">@lang('state.empty')</p>
            @endif

            {{-- The membership notice from the 2024 page. It is the single most
                 useful line on this page for the majority of visitors, because
                 most of them are already members and are buying at the member
                 rate; it is kept above the cards rather than below them. --}}
            <div class="alrd-member mt-4">
                <p>@lang('login.member_notice')</p>
            </div>

            <div class="row g-4 justify-content-center">
                @foreach ($ticketTypes as $type)
                    @php
                        // Which price applies is decided by the server from the
                        // Membership record, never from anything the visitor chose.
                        // Showing the member rate to an active member is a
                        // convenience; it grants nothing.
                        $amount = $type->priceFor($isMember);
                    @endphp

                    <div class="col-lg-6 col-12">
                        <article class="price-card text-center">
                            <div class="price-header" @if ($type->colour) style="background-color: {{ $type->colour }};" @endif>
                                <span class="price-plan">{{ $type->name }}</span>
                            </div>

                            <div class="price-body">
                                <ul class="price-desc">
                                    <li>
                                        {{-- dir="ltr" so the amount is not bidi-reordered
                                             next to a currency symbol on the Arabic page. --}}
                                        <span class="price-amount" dir="ltr">{{ $type->formatAmount($amount) }}</span>
                                    </li>
                                    <li class="text-muted">
                                        @lang($isMember ? 'pricing.member' : 'pricing.standard')
                                    </li>
                                    @if ($type->description)
                                        <li>{{ $type->description }}</li>
                                    @endif
                                </ul>

                                @if (! empty($type->includes))
                                    <p class="mt-3 fw-bold">@lang('pricing.includes')</p>
                                    <ul class="price-desc text-start">
                                        @foreach ((array) $type->includes as $item)
                                            <li>
                                                <span class="text-success me-2" aria-hidden="true">&#10003;</span>
                                                {{ $item }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                <div class="mt-4">
                                    @if (! $registrationOpen)
                                        <span class="btn btn-secondary w-100 disabled">@lang('pricing.closed')</span>
                                    @else
                                        <a href="{{ route('register') }}" class="btn-join w-100 text-center">
                                            @lang('pricing.register')
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

@endsection
