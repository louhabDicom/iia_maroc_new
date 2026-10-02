@extends('layouts.app')

@section('title', __('order.payment.cancelled'))

@section('content')
    <div class="section-padding-04">
        <div class="container text-center">

            <h1 class="h3">@lang('order.payment.cancelled')</h1>

            {{-- Explicitly reassuring: the 2024 flow left people unsure
                 whether they had been charged, because abandoning the 3D
                 Secure step produced no page at all. --}}
            <p class="text-muted">@lang('order.payment.cancelled_note')</p>

            <p dir="ltr" class="mt-3">
                <span class="text-muted">@lang('order.reference'):</span>
                <span class="font-mono">{{ $order->reference }}</span>
            </p>

            <div class="mt-4 d-flex justify-content-center gap-2 flex-wrap">
                @if ($order->isPayable())
                    <a href="{{ route('checkout.pay', ['order' => $order->id]) }}" class="btn-join btn-cirle">
                        @lang('order.payment.retry')
                    </a>
                @endif

                <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">
                    @lang('nav.registration')
                </a>
            </div>
        </div>
    </div>
@endsection