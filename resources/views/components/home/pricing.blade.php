{{--
    What it costs.

    Four cards rather than two, because the two that matter are a cross: a
    ticket type fixes the currency, and every type has a member rate and a
    standard rate. Rendering one card per type and hiding the rate the visitor
    is not eligible for was the previous behaviour, and it made the band
    answer a question nobody asked — "what if I am not a member?" — while
    hiding the one they did.

    Nothing here decides who is a member. The cards are a price list; the rate
    actually charged is resolved server-side at checkout from the Membership
    record, exactly as before.

    The figure and the currency are two elements rather than one formatted
    string so the currency can sit smaller and lighter. They are produced by
    `amountParts()` and not by splitting `formatAmount()`'s output, because
    the thousands separator is a narrow no-break space and cutting on a plain
    space silently breaks the number in French.
--}}
@props(['ticketTypes', 'edition', 'isMember' => false])

@php
    $localCurrency = (string) config('conference.default_currency');

    // One card per (type, rate) pair. The audience line is the only thing that
    // differs between the two cards of a type.
    $tariffs = [];

    foreach ($ticketTypes as $type) {
        foreach ([true, false] as $member) {
            $parts = $type->amountParts($type->priceFor($member));

            $tariffs[] = [
                'amount' => $parts['amount'],
                'currency' => $parts['currency'],
                'kind' => $type->currency === $localCurrency
                    ? __('home.landing.inscription.ttc')
                    : __('home.landing.inscription.fx'),
                'audience' => $member
                    ? __('home.landing.inscription.members')
                    : __('home.landing.inscription.non_members'),
                'code' => $type->code,
            ];
        }
    }

    // One call to action per currency on sale, so the two buttons under the
    // grid take the visitor to the rate they actually intend to pay in.
    $currencies = $ticketTypes->pluck('currency')->filter()->unique()->values();
@endphp

@if ($tariffs !== [])
    <section class="h-inscription" aria-labelledby="inscription-title">
        <div class="container">

            <h2 id="inscription-title" class="h-inscription__title">
                @lang('home.landing.inscription.title')
            </h2>

            <ul class="h-tariffs">
                @foreach ($tariffs as $tariff)
                    <li class="h-tariff">
                        <span class="h-tariff__amount" dir="ltr">
                            <span class="h-tariff__figure">{{ $tariff['amount'] }}</span>
                            <span class="h-tariff__currency">{{ $tariff['currency'] }}</span>
                        </span>

                        <span class="h-tariff__kind">{{ $tariff['kind'] }}</span>
                        <span class="h-tariff__audience">{{ $tariff['audience'] }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="h-inscription__actions">
                @if ($edition->registration_open)
                    @foreach ($currencies as $currency)
                        <a href="{{ auth()->check() ? route('pricing') : route('register') }}"
                           class="h-btn h-btn--outline">
                            @lang('home.landing.inscription.reserve', ['currency' => $currency])
                        </a>
                    @endforeach
                @else
                    {{-- A real disabled button rather than a styled span: it
                         looks identical, and a screen reader still reports it
                         as unavailable. --}}
                    <button type="button" class="h-btn h-btn--outline" disabled>
                        @lang('pricing.closed')
                    </button>
                @endif
            </div>

        </div>
    </section>
@endif