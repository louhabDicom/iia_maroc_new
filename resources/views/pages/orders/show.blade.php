@extends('layouts.app')

@section('title', __('order.title'))

@section('content')
    <x-page-hero
        :title="__('order.title')"
        :crumbs="[__('account.orders') => route('orders.index'), __('order.reference') => null]"
        image="assets/images/bg/price_bg.jpg" />

    <div class="section-padding-04">
        <div class="container">

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row g-4">
                <div class="col-lg-8">

                    <h1 class="h4">
                        @lang('order.reference'):
                        <span dir="ltr" class="font-monospace">{{ $order->reference }}</span>
                    </h1>

                    <p>
                        <span class="badge
                            @if ($order->status->colour() === 'success') text-bg-success
                            @elseif ($order->status->colour() === 'danger') text-bg-danger
                            @elseif ($order->status->colour() === 'info') text-bg-info
                            @else text-bg-warning @endif">
                            {{ $order->status->label($currentLocale->value) }}
                        </span>
                    </p>

                    <p class="text-muted">@lang('order.placed_on'): {{ $order->created_at->translatedFormat('d F Y H:i') }}</p>

                    <h2 class="h5 mt-4">@lang('order.items')</h2>

                    <table class="table align-middle">
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

                    <h2 class="h5 mt-4">@lang('order.participants')</h2>

                    <ul class="list-group">
                        @foreach ($order->participants as $participant)
                            <li class="list-group-item">
                                <span class="fw-semibold">{{ $participant->full_name }}</span>
                                @if ($participant->is_member)
                                    <span class="badge bg-success">@lang('pricing.member')</span>
                                @endif
                                @if ($participant->job_title)
                                    <div class="text-muted small">{{ $participant->job_title }}</div>
                                @endif
                                @if ($participant->email)
                                    <div class="text-muted small" dir="ltr">{{ $participant->email }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="col-lg-4">
                    <div class="price-card">
                        <div class="price-body text-center">

                            {{-- The invoice is only offered once settled. A
                                 paid order is refunded rather than cancelled,
                                 and the two are not interchangeable. --}}
                            @if ($order->status->isSettled())
                                <form method="POST" action="{{ route('orders.invoice', ['order' => $order->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn-join w-100 text-center d-block">
                                        @lang('order.download_invoice')
                                    </button>
                                </form>

                                @if ($order->invoice_number)
                                    <p class="text-muted small mt-2" dir="ltr">
                                        {{ $order->invoice_number }}
                                    </p>
                                @endif
                            @elseif ($order->isPayable())
                                <a href="{{ route('checkout.pay', ['order' => $order->id]) }}"
                                   class="btn-join w-100 text-center d-block">
                                    @lang('order.pay_now')
                                </a>
                            @else
                                <p class="text-muted">@lang('order.invoice.not_available')</p>
                            @endif

                            @unless ($order->status->isSettled() || $order->status->isFinal())
                                <form method="POST" action="{{ route('orders.cancel', ['order' => $order->id]) }}"
                                      class="mt-3"
                                      onsubmit="return confirm(@js(__('order.cancel.confirm')));">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger w-100">
                                        @lang('order.cancel.action')
                                    </button>
                                </form>
                            @endunless

                            @if ($order->latestPayment)
                                <div class="mt-4 text-start border-top pt-3">
                                    <p class="small text-muted mb-1">@lang('order.payment.last_attempt')</p>
                                    <p class="small mb-0" dir="ltr">
                                        {{ $order->latestPayment->formattedAmount() }}
                                        — {{ $order->latestPayment->status->value }}
                                        @if ($order->latestPayment->cardLabel() !== '—')
                                            <br>{{ $order->latestPayment->cardLabel() }}
                                        @endif
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection