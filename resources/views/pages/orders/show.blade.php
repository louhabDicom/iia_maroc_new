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

            @if ($errors->any())
                <div class="ux-notice ux-notice--danger mb-4" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('status'))
                <div class="ux-notice ux-notice--success mb-4" role="status">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Before payment the page is fully usable — open, print, pay — so
                 this says what is still missing (the numbered facture) rather
                 than leaving the delegate to wonder whether they are looking at
                 a receipt or the real thing. --}}
            @unless ($order->status->isSettled())
                <div class="ux-notice ux-notice--warning mb-4">
                    {{ __('order.unpaid_notice') }}
                </div>
            @endunless

            {{-- Reference, status and total in one band. An invoice is the one
                 page a delegate returns to out of anxiety, so it answers its
                 two questions before any scrolling. --}}
            <div class="ux-card ux-card--edge ux-radius-xl p-4 mb-5 ux-reveal">
                <div class="row g-4">

                    <div class="col-12 col-md-6">
                        <p class="ux-stat__label">@lang('order.reference')</p>
                        <p class="ux-stat mb-0 font-monospace" dir="ltr">{{ $order->reference }}</p>
                    </div>

                    <div class="col-6 col-md-3">
                        <p class="ux-stat__label">@lang('order.status')</p>
                        <p class="mb-0 mt-1">
                            {{-- Colour here is load-bearing: red has to read as
                                 "this needs attention" at a glance. The state is
                                 also carried by the label text, so it never
                                 depends on the reader perceiving the hue. --}}
                            @php($statusColour = $order->status->colour())
                            <span @class([
                                'ux-tag',
                                'ux-tag--solid' => $statusColour === 'danger',
                                'ux-tag--gold' => $statusColour === 'warning',
                                'ux-tag--cyan' => $statusColour === 'info',
                            ])>
                                {{ $order->status->label($currentLocale->value) }}
                            </span>
                        </p>
                    </div>

                    <div class="col-6 col-md-3">
                        <p class="ux-stat__label">@lang('order.total')</p>
                        <p class="ux-stat mb-0" dir="ltr">{{ $order->formattedTotal() }}</p>
                    </div>

                </div>

                <p class="ux-ink-soft small mt-3 mb-0">
                    @lang('order.placed_on'):
                    <time datetime="{{ $order->created_at->toIso8601String() }}">
                        {{ $order->created_at->translatedFormat('d F Y H:i') }}
                    </time>
                </p>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">

                    <div class="ux-card ux-card--edge ux-radius-xl p-4 mb-4 ux-reveal">
                        <h2 class="h5 ux-card__title">@lang('order.items')</h2>

                        {{-- table-responsive, because the amount column is a
                             currency string and a narrow phone otherwise
                             squeezes the ticket name into a one-word column. --}}
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <caption class="visually-hidden">@lang('order.items')</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">@lang('order.ticket')</th>
                                        <th scope="col" class="text-center">@lang('order.quantity')</th>
                                        <th scope="col" class="text-end">@lang('order.amount')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->items as $item)
                                        <tr>
                                            <th scope="row" class="fw-normal">{{ $item->label }}</th>
                                            <td class="text-center">{{ $item->totalQuantity() }}</td>
                                            <td class="text-end" dir="ltr">
                                                {{ $item->formattedLineTotal($order->currency) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-top">
                                        <th scope="row" colspan="2" class="text-end">@lang('order.total')</th>
                                        <td class="text-end" dir="ltr">
                                            <span class="fw-bold">{{ $order->formattedTotal() }}</span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
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

                    <div class="ux-card ux-card--edge ux-radius-xl p-4 ux-reveal">
                        <h2 class="h5 ux-card__title">@lang('order.participants')</h2>

                        {{-- An order with no named attendees is not a broken
                             page: it is one paid for before the delegates were
                             entered, and the organiser collects that list
                             separately. Say so rather than rendering nothing. --}}
                        @if ($order->participants->isEmpty())
                            <x-empty-state
                                :message="__('order.participants_pending')"
                                icon="fa-regular fa-user-clock" />
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach ($order->participants as $participant)
                                    <li class="list-group-item bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2 px-0">
                                        <div>
                                            <span class="fw-semibold">{{ $participant->full_name }}</span>
                                            @if ($participant->is_member)
                                                <span class="ux-tag ux-tag--gold ms-2">@lang('pricing.member')</span>
                                            @endif
                                            @if ($participant->job_title)
                                                <div class="ux-ink-soft small">{{ $participant->job_title }}</div>
                                            @endif
                                        </div>

                                        {{-- The address is the only handle on an
                                             attendee here, so it is a mailto
                                             rather than inert grey text. --}}
                                        @if ($participant->email)
                                            <a href="mailto:{{ $participant->email }}"
                                               class="small text-break ux-ink-soft">
                                                {{ $participant->email }}
                                            </a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
                <div class="col-lg-4">
                    {{-- Sticky on a wide screen: the download button is the
                         thing a delegate came back for, and it should not have
                         to be scrolled to after reading a long attendee list.
                         It is not sticky on a phone, where the column sits under
                         the content anyway. --}}
                    <div class="ux-card ux-card--glass ux-radius-xl p-4 text-center sticky-lg-top ux-reveal ux-reveal-right ux-invoice__summary">
                        {{-- `ux-print-hide`: a button on a printed invoice is a
                             rectangle of toner with no function. The figures it
                             sits next to are kept. --}}
                        <h2 class="h5 ux-card__title ux-print-hide">@lang('order.summary')</h2>

                        <p class="ux-stat mb-4" dir="ltr">{{ $order->formattedTotal() }}</p>

                        {{-- The invoice is only offered once settled. A paid
                             order is refunded rather than cancelled, and the
                             two are not interchangeable. --}}
                        @if ($order->status->isSettled())
                            <form method="POST"
                                  action="{{ route('orders.invoice', ['order' => $order->id]) }}"
                                  class="ux-print-hide">
                                @csrf
                                <button type="submit" class="btn-join w-100 text-center d-block">
                                    @lang('order.download_invoice')
                                </button>
                            </form>

                            {{-- Browser print. The PDF above is the document to
                                 keep, but it only exists once the order is
                                 settled; this gives a delegate a copy whatever
                                 the state, and costs no round trip. --}}
                            <button type="button"
                                    class="ux-btn ux-btn--ghost ux-btn--sm w-100 mt-2 ux-print-hide"
                                    onclick="window.print()">
                                <span>@lang('order.print')</span>
                            </button>

                            @if ($order->invoice_number)
                                <p class="ux-ink-soft small mt-2 mb-0" dir="ltr">
                                    {{ $order->invoice_number }}
                                </p>
                            @endif
                        @elseif ($order->isPayable())
                            <a href="{{ route('checkout.pay', ['order' => $order->id]) }}"
                               class="btn-join w-100 text-center d-block ux-print-hide">
                                @lang('order.pay_now')
                            </a>

                            <button type="button"
                                    class="ux-btn ux-btn--ghost ux-btn--sm w-100 mt-2 ux-print-hide"
                                    onclick="window.print()">
                                <span>@lang('order.print')</span>
                            </button>
                        @else
                            <p class="ux-ink-soft mb-0">@lang('order.invoice.not_available')</p>

                            <button type="button"
                                    class="ux-btn ux-btn--ghost ux-btn--sm w-100 mt-2 ux-print-hide"
                                    onclick="window.print()">
                                <span>@lang('order.print')</span>
                            </button>
                        @endif

                        @unless ($order->status->isSettled() || $order->status->isFinal())
                            <form method="POST"
                                  action="{{ route('orders.cancel', ['order' => $order->id]) }}"
                                  class="mt-3 ux-print-hide"
                                  onsubmit="return confirm(@js(__('order.cancel.confirm')));">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    @lang('order.cancel.action')
                                </button>
                            </form>
                            @endunless

                        {{-- Printed too: it explains the button above, and a
                             reader holding a paper copy has no button left to
                             rediscover it from. --}}
                        <p class="ux-ink-soft small mt-3 mb-0">
                            @lang('order.print_hint')
                        </p>

                            {{-- Resolved once. Reading it four times meant four
                                 queries for one block of text, and property
                                 syntax on a plain method is what made this a
                                 fatal — see Order::latestPaymentRecord(). --}}
                            @php($latestPayment = $order->latestPaymentRecord())

                            @if ($latestPayment)
                                <div class="mt-4 text-start border-top pt-3">
                                    <p class="small text-muted mb-1">@lang('order.payment.last_attempt')</p>
                                    <p class="small mb-0" dir="ltr">
                                        {{ $latestPayment->formattedAmount() }}
                                        — {{ $latestPayment->status->value }}
                                        @if ($latestPayment->cardLabel() !== '—')
                                            <br>{{ $latestPayment->cardLabel() }}
                                        @endif
                                    </p>
                                </div>
                            @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- An invoice is the end of a purchase, not a dead end: what a delegate
         wants next is the thing the ticket admits them to. --}}
    <x-cta-band
        :title="__('nav.programme')"
        :text="__('order.cta_lede')"
        :primary-label="__('nav.programme')"
        :primary-url="route('programme')"
        :secondary-label="__('nav.venue')"
        :secondary-url="route('venue')" />
@endsection