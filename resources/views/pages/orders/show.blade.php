@extends('layouts.app')

@section('title', __('order.title'))

{{-- Marks this page as printable. The print block at the foot of ux.css keys
     off this class to drop the header, footer and call-to-action band and keep
     the document itself. --}}
@push('body-class')
    ux-printable
@endpush

@section('content')
    {{-- The reference is the hero's crumb rather than its heading: an invoice is
         identified by a string like ARABCIA-2026/2026/00042, and making that the
         <h1> pushes the two things a delegate actually opened the page to see —
         what they paid, and whether it is settled — below the fold. --}}
    <x-page-hero
        :title="__('order.title')"
        :crumbs="[__('account.orders') => route('orders.index'), $order->reference => null]"
        image="assets/images/bg/price_bg.jpg" />

    <section class="ux-section">
        <div class="container">

            {{-- The invoice number is the page's real title, exactly as it is
                 on the printed document. Hiding it inside the breadcrumb made a
                 delegate hunt for the one string that identifies the order. --}}
            <div class="ux-card ux-card--edge ux-radius-xl p-4 p-lg-5 ux-reveal ux-invoice">

                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <h1 class="h3 mb-0">
                            {{ __('order.invoice_heading', ['reference' => $order->reference]) }}
                        </h1>

                        @if ($order->invoice_number)
                            <p class="ux-ink-soft small mt-2 mb-0" dir="ltr">
                                {{ $order->invoice_number }}
                            </p>
                        @endif
                    </div>

                    {{-- Outlined, not filled: the badge states the state, and a
                         solid block would compete with the total for the eye. --}}
                    @php($statusColour = $order->status->colour())
                    <span @class([
                        'ux-invoice__status',
                        'ux-invoice__status--'.$statusColour => in_array($statusColour, ['success', 'danger', 'warning', 'info'], true),
                    ])>
                        {{ $order->status->label($currentLocale->value) }}
                    </span>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <p class="ux-stat__label mb-1">@lang('order.paid_to')</p>
                        <p class="mb-0">
                            @if (filled($order->billing['organisation'] ?? null))
                                {{ $order->billing['organisation'] }}
                            @else
                                {{ trim(($order->billing['first_name'] ?? '').' '.($order->billing['last_name'] ?? '')) }}
                            @endif
                        </p>
                    </div>

                    <div class="col-md-6">
                        <p class="ux-stat__label mb-1">@lang('order.payment_method')</p>
                        <p class="mb-0">@lang('order.payment_method_cmi')</p>
                    </div>
                </div>

                {{-- One row per seat, not per line item: the delegate is being
                     invoiced for named people, and an order of two standard
                     seats and one member seat is three rows they can check. --}}
                <div class="table-responsive">
                    <table class="table ux-invoice__table align-middle mb-0">
                        <caption class="visually-hidden">@lang('order.items')</caption>
                        <thead>
                            <tr>
                                <th scope="col">@lang('order.offer')</th>
                                <th scope="col">@lang('order.participant_name')</th>
                                <th scope="col" class="d-none d-lg-table-cell">@lang('order.email')</th>
                                <th scope="col" class="d-none d-lg-table-cell">@lang('order.phone')</th>
                                <th scope="col" class="d-none d-md-table-cell">@lang('order.date')</th>
                                <th scope="col" class="text-end">@lang('order.price_incl')</th>
                            </tr>
                        </thead>
                        <tbody>
                                    {{-- Seats with a named attendee, one row each. The
                                         price shown is that seat's own unit
                                         price, so a member discount shows up in
                                         the rows rather than only in the total. --}}
                                    @forelse ($order->participants as $participant)
                                        @php
                                            // Walk the order's lines in step with the
                                            // attendees so each row can be priced at
                                            // the rate that seat was actually sold at.
                                            $line = $order->items->get($loop->index)
                                                ?? $order->items->last();
                                        @endphp

                                        <tr>
                                            <th scope="row" class="fw-normal">{{ $line?->label ?? __('order.ticket') }}</th>
                                            <td>
                                                {{ $participant->full_name }}
                                                @if ($participant->is_member)
                                                    <span class="ux-tag ux-tag--gold ms-1">@lang('pricing.member')</span>
                                                @endif
                                                {{-- Contact details are stacked under
                                                     the name on narrow screens, where
                                                     their own columns are dropped. --}}
                                                @if ($participant->email)
                                                    <span class="d-lg-none d-block ux-ink-soft small" dir="ltr">
                                                        {{ $participant->email }}
                                                    </span>
                                                @endif
                                                @if ($participant->phone)
                                                    <span class="d-lg-none d-block ux-ink-soft small" dir="ltr">
                                                        {{ $participant->phone }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="d-none d-lg-table-cell" dir="ltr">{{ $participant->email }}</td>
                                            <td class="d-none d-lg-table-cell" dir="ltr">{{ $participant->phone }}</td>
                                            <td class="d-none d-md-table-cell">
                                                {{ $order->created_at->translatedFormat('d/m/Y') }}
                                            </td>
                                            <td class="text-end" dir="ltr">
                                                {{ $line?->formattedSeatPrice($order->currency) ?? '—' }}
                                            </td>
                                        </tr>
                                    @empty
                                        {{-- Seats bought before the attendees were
                                             named. Quantity carries the line here,
                                             because there is no one to attach it
                                             to — the totals below stay exact. --}}
                                        @foreach ($order->items as $item)
                                            <tr>
                                                <th scope="row" class="fw-normal">{{ $item->label }}</th>
                                                <td class="ux-ink-soft">@lang('order.participants_pending')</td>
                                                <td class="d-none d-lg-table-cell"></td>
                                                <td class="d-none d-lg-table-cell"></td>
                                                <td class="d-none d-md-table-cell">
                                                    {{ $order->created_at->translatedFormat('d/m/Y') }}
                                                </td>
                                                <td class="text-end" dir="ltr">
                                                    <span class="d-md-none d-block ux-ink-soft small">
                                                        × {{ $item->totalQuantity() }}
                                                    </span>
                                                    {{ $item->formattedLineTotal($order->currency) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Totals, offset to the reading edge rather than
                             stretched full width: a column of three numbers
                             spread across 900px is harder to add up than one
                             held at a readable measure. --}}
                        <div class="ux-invoice__totals mt-4">
                            <dl class="mb-0">
                                <div class="ux-invoice__row">
                                    <dt>@lang('order.subtotal')</dt>
                                    <dd dir="ltr">{{ \App\Support\Money::format($order->subtotal, $order->currency, $currentLocale->value) }}</dd>
                                </div>

                                @if ($order->discount_total > 0)
                                    <div class="ux-invoice__row">
                                        <dt>@lang('order.member_saving')</dt>
                                        <dd dir="ltr">−{{ \App\Support\Money::format($order->discount_total, $order->currency, $currentLocale->value) }}</dd>
                                    </div>
                                @endif

                                <div class="ux-invoice__row">
                                    <dt>@lang('order.vat')</dt>
                                    <dd dir="ltr">{{ \App\Support\Money::format($order->tax_total, $order->currency, $currentLocale->value) }}</dd>
                                </div>

                                <div class="ux-invoice__row ux-invoice__row--total">
                                    <dt>@lang('order.total_incl')</dt>
                                    <dd dir="ltr">{{ $order->formattedTotal() }}</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- The confirm step. Before payment this is the call to action; afterwards
                             it disappears and the PDF takes its place. Print sits
                             beside it in both states, so a delegate who wants a
                             copy now is never told to come back later. --}}
                         <div class="d-flex flex-wrap gap-3 align-items-center ux-print-hide mt-4">
                            @if ($order->isPayable())
                                <a href="{{ route('checkout.pay', ['order' => $order->id]) }}"
                                   class="btn-join mb-0"
                                   data-ux-magnetic="0.2">
                                    @lang('order.confirm_and_pay')
                                    <i class="fas fa-arrow-right ms-2" aria-hidden="true"></i>
                                </a>
                            @endif

                            @if ($order->status->isSettled())
                                <form method="POST"
                                      action="{{ route('orders.invoice', ['order' => $order->id]) }}"
                                      class="m-0">
                                    @csrf
                                    <button type="submit" class="ux-btn ux-btn--ghost ux-btn--sm mb-0">
                                        <span>@lang('order.download_invoice')</span>
                                    </button>
                                </form>
                            @endif

                            <button type="button"
                                    class="ux-btn ux-btn--ghost ux-btn--sm mb-0 ms-auto"
                                    onclick="window.print()">
                                <i class="fas fa-print me-2" aria-hidden="true"></i>
                                <span>@lang('order.download_invoice_short')</span>
                            </button>
                        </div>

                        @unless ($order->status->isSettled() || $order->status->isFinal())
                            <div class="mt-3 ux-print-hide">
                                <form method="POST"
                                      action="{{ route('orders.cancel', ['order' => $order->id]) }}"
                                      onsubmit="return confirm(@js(__('order.cancel.confirm')));">
                                    @csrf
                                    <button type="submit" class="btn btn-link btn-sm text-danger px-0">
                                        @lang('order.cancel.action')
                                    </button>
                                </form>
                            </div>
                        @endunless
                    </div>

                    {{-- Who the invoice is addressed to. A delegate who needs
                         to put this through their institution's expense system
                         looks for the billing address here, not in the
                         participants list, so it is stated in full and kept in
                         the printed output. --}}
                    <div class="ux-card ux-card--edge ux-radius-xl p-4 mt-4 ux-reveal">
                        <h2 class="h5 ux-card__title">@lang('order.billing_details')</h2>

                        @php($billing = $order->billing ?? [])

                        <address class="mb-0 ux-ink-soft">
                            @if (filled($billing['organisation'] ?? null))
                                <strong class="d-block">{{ $billing['organisation'] }}</strong>
                            @endif

                            {{ filled($billing['first_name'] ?? null) || filled($billing['last_name'] ?? null)
                                ? trim(($billing['first_name'] ?? '').' '.($billing['last_name'] ?? ''))
                                : null }}

                            @if (filled($billing['address'] ?? null))
                                <span class="d-block">{{ $billing['address'] }}</span>
                            @endif

                            @if (filled($billing['city'] ?? null))
                                <span class="d-block">
                                    {{ $billing['city'] }}@if (filled($billing['country_iso2'] ?? null)), {{ $billing['country_iso2'] }}@endif
                                </span>
                            @endif

                            @if (filled($billing['email'] ?? null))
                                <span class="d-block" dir="ltr">{{ $billing['email'] }}</span>
                            @endif

                            @if (filled($billing['phone'] ?? null))
                                <span class="d-block" dir="ltr">{{ $billing['phone'] }}</span>
                            @endif
                        </address>
                    </div>

                    {{-- The last payment attempt, kept because a delegate whose
                         payment failed needs to see what was refused. --}}
                    @php($latestPayment = $order->latestPaymentRecord())

                    @if ($latestPayment)
                        <div class="ux-notice ux-notice--warning mt-4">
                            <p class="mb-0 small">
                                <strong>@lang('order.payment.last_attempt'):</strong>
                                <span dir="ltr">
                                    {{ $latestPayment->formattedAmount() }} — {{ $latestPayment->status->value }}
                                    @if ($latestPayment->cardLabel() !== '—')
                                        <br>{{ $latestPayment->cardLabel() }}
                                    @endif
                                </span>
                            </p>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </section>

    {{-- Reassurance strip. An invoice page is where someone is about to enter
         card details or ask their bank to explain a charge, so the four things
         they are wondering about are answered before they leave. --}}
    <section class="ux-section ux-section--tint">
        <div class="container">
            <div class="row g-4">
                @foreach ([[
                    ['fa-shield-halved', 'secure'],
                    ['fa-credit-card', 'cards'],
                    ['fa-file-invoice', 'instant'],
                    ['fa-headset', 'support'],
                ] as [$icon, $key])
                    <div class="col-6 col-lg-3">
                        <div class="ux-feature ux-feature--stacked h-100">
                            <span class="ux-feature__icon ux-feature__icon--cool">
                                <i class="fas {{ $icon }}" aria-hidden="true"></i>
                            </span>
                            <h3 class="ux-feature__title">
                                {{ __("order.assurances.{$key}") }}
                            </h3>
                            <p class="ux-feature__text">
                                {{ __("order.assurances.{$key}_text") }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Support, not a sales pitch: the only question left at this point is
         about the order itself. --}}
    <x-cta-band
        :title="__('order.help_title')"
        :text="__('order.help_text')"
        :primary-label="__('order.contact_us')"
        :primary-url="route('contact')" />
@endsection

