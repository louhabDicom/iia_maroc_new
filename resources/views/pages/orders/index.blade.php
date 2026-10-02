@extends('layouts.app')

@section('title', __('account.orders'))

@section('content')
    <x-page-hero
        :title="__('account.orders')"
        :crumbs="[__('account.orders') => null]"
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

            @if ($orders->isEmpty())
                <p class="empty-state">
                    @lang('order.no_orders')
                    <a href="{{ route('pricing') }}" class="d-block mt-2">@lang('pricing.register')</a>
                </p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <caption class="visually-hidden">@lang('account.orders')</caption>
                        <thead>
                            <tr>
                                <th scope="col">@lang('order.reference')</th>
                                <th scope="col">@lang('order.placed_on')</th>
                                <th scope="col">@lang('order.status')</th>
                                <th scope="col" class="text-end">@lang('order.amount')</th>
                                <th scope="col"><span class="visually-hidden">@lang('action.view')</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr>
                                    <th scope="row" class="fw-normal">
                                        {{-- dir="ltr" and a monospace face: an
                                             invoice reference is a code, not
                                             prose, and must survive the Arabic
                                             page unchanged. --}}
                                        <span dir="ltr" class="font-monospace">{{ $order->reference }}</span>
                                    </th>
                                    <td>{{ $order->created_at->translatedFormat('d M Y') }}</td>
                                    <td>
                                        <span @class([
                                            'badge',
                                            'text-bg-warning' => $order->status->colour() === 'warning',
                                            'text-bg-success' => $order->status->colour() === 'success',
                                            'text-bg-danger'  => $order->status->colour() === 'danger',
                                            'text-bg-info'    => $order->status->colour() === 'info',
                                        ])>
                                            {{ $order->status->label($currentLocale->value) }}
                                        </span>
                                    </td>
                                    <td class="text-end" dir="ltr">
                                        {{ \App\Support\Money::format($order->total, $order->currency, $currentLocale->value) }}
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('orders.show', ['order' => $order->id]) }}"
                                           class="btn btn-sm btn-outline-primary">@lang('action.view')</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $orders->links() }}
            @endif
        </div>
    </div>
@endsection