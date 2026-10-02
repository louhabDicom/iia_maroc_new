@extends('layouts.app')

@section('title', __('order.payment.pending'))

@section('content')
    {{-- The gap between the customer returning from the gateway and the
         authoritative server-to-server callback landing is normally a few
         hundred milliseconds. This page refreshes itself until the order
         settles, and says plainly what is happening rather than showing a
         spinner forever or — as the 2024 site did — redirecting to the home
         page as though nothing had occurred. --}}
    <meta http-equiv="refresh" content="3;url={{ route('payment.return', ['order' => $order->id]) }}">

    <div class="section-padding-04">
        <div class="container text-center">
            <div class="d-inline-block spinner-border" role="status">
                <span class="visually-hidden">@lang('order.payment.pending')</span>
            </div>

            <h1 class="h3 mt-4">@lang('order.payment.pending')</h1>
            <p class="text-muted">@lang('order.payment.pending_note')</p>

            <p class="mt-4">
                <a href="{{ route('orders.show', ['order' => $order->id]) }}" class="btn btn-outline-secondary">
                    @lang('order.title')
                </a>
            </p>
        </div>
    </div>
@endsection