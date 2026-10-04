{{--
    The basket.

    The lines are tiles rather than a table. A table is the right semantic for a
    spreadsheet and the wrong one for three rows on a phone: a five-column table
    scrolls sideways on a narrow screen, and the quantity control — the only thing
    a delegate actually interacts with — ends up off-screen. Each line is its own
    card so it reflows instead of scrolling.

    Each line also keeps its own form rather than sharing one for the page, so a
    validation error on one line cannot silently discard edits made to another.
--}}
@extends('layouts.app')

@section('title', __('order.cart.title'))
@section('description', __('order.cart.subtitle'))

@section('content')

    <x-page-hero
        :title="__('order.cart.title')"
        :crumbs="[__('order.cart.title') => null]"
        image="assets/images/bg/price_bg.jpg" />

    <section class="ux-section section-padding-03 ux-section--defer"
             aria-labelledby="cart-lines-heading">
        <div class="container">
            <div class="row g-4">

                <div class="col-lg-8 ux-reveal ux-reveal-left">
                    <x-section-head
                        id="cart-lines-heading"
                        :eyebrow="__('order.cart.title')"
                        :title="__('order.summary')"
                        :level="2"
                        :lede="__('order.cart.subtitle')" />

                    @if ($errors->any())
                        <div class="ux-notice ux-notice--danger mt-4" role="alert">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <ul class="row g-3 list-unstyled mt-2" data-ux-stagger="70">
                        @foreach ($cart->items as $item)
                            <li class="col-12">
                                <article class="ux-card ux-card--lift ux-card--edge p-4">
                                    <div class="row g-3 align-items-center">

                                        <div class="col-sm">
                                            <h3 class="ux-card__title h6 mb-1">
                                                {{ $item->ticketType?->name ?? __('order.ticket') }}
                                            </h3>
                                            @if ($item->ticketType?->description)
                                                <p class="ux-ink-soft small mb-0">
                                                    {{ $item->ticketType->description }}
                                                </p>
                                            @endif
                                        </div>

                                        <div class="col-sm-auto">
                                            <form method="POST"
                                                  action="{{ route('cart.update', ['cart' => $cart->id, 'ticketType' => $item->ticket_type_id]) }}"
                                                  class="d-flex align-items-center gap-2">
                                                @csrf
                                                <label class="visually-hidden" for="qty-{{ $item->id }}">
                                                    @lang('order.quantity')
                                                </label>
                                                <input type="number" id="qty-{{ $item->id }}" name="quantity"
                                                       value="{{ $item->quantity }}" min="0" max="20"
                                                       class="form-control form-control-sm" style="width: 5rem;">
                                                <input type="hidden" name="member_quantity"
                                                       value="{{ $item->member_quantity }}">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                    @lang('action.update')
                                                </button>
                                            </form>
                                        </div>

                                        <div class="col-sm-auto text-sm-end">
                                            {{-- dir="ltr": an amount beside a currency
                                                 code must not be bidi-reordered on the
                                                 Arabic page. --}}
                                            <p class="ux-card__title mb-0" dir="ltr">
                                                {{ $item->formattedEstimatedTotal() }}
                                            </p>
                                            <form method="POST"
                                                  action="{{ route('cart.destroy', ['cart' => $cart->id, 'ticketType' => $item->ticket_type_id]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-link text-danger p-0">
                                                    @lang('action.remove')
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </article>
                            </li>
                        @endforeach
                    </ul>


                    {{-- The estimate is explicitly labelled as one. The authoritative
                         figure is the quote at checkout, because that is where the
                         member rate is applied from the server's own membership record
                         rather than from what the visitor claimed. --}}
                    <p class="ux-ink-soft small mt-3 mb-0">@lang('order.cart.estimate_note')</p>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a href="{{ route('pricing') }}" class="btn btn-outline-secondary">
                            @lang('order.cart.continue')
                        </a>
                        <form method="POST" action="{{ route('cart.clear') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger">
                                @lang('order.cart.clear')
                            </button>
                        </form>
                    </div>
                </div>

                {{-- The summary is its own column rather than a table footer: on a
                     phone it is the only thing visible without scrolling, which is
                     exactly where the total and the call to action belong. --}}
                <div class="col-lg-4 ux-reveal ux-reveal-right">
                    <div class="ux-card ux-card--glass ux-radius-xl p-4 text-center">
                        <h2 class="h5 ux-card__title">@lang('order.summary')</h2>

                        <p class="ux-ink-soft">@lang('order.cart.summary_note')</p>

                        <p class="ux-card__title my-4" dir="ltr">
                            {{ \App\Support\Money::format($estimatedTotal, $currency) }}
                        </p>

                        {{-- Both paths lead to the same place. Signed out, the checkout
                             sends the visitor to sign in and keeps the basket, so they
                             come back to it. --}}
                        <a href="{{ auth()->check() ? route('checkout') : route('login') }}"
                           class="btn-join w-100 text-center d-block">@lang('order.cart.checkout')</a>

                        @guest
                            <p class="ux-ink-soft small mt-2 mb-0">@lang('order.cart.sign_in_note')</p>
                        @endguest
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
