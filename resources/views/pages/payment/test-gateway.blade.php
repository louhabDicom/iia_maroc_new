{{--
    The local payment rehearsal page, served only when PAYMENT_DRIVER=test.

    It exists so the whole checkout — order creation, signed payload, server to
    server callback, settlement, invoice issue, notification — can be walked
    end to end without a card. Both buttons post a correctly signed payload to
    the real callback route, so what is being tested is the production code
    path and not a simulation of it.

    PaymentController refuses to render this unless the test driver is the
    configured one, so it cannot exist in production.
--}}
@extends('layouts.app')

@section('title', __('order.payment.test_title'))

@section('content')
    <div class="section-padding-04">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    <div class="alert alert-warning" role="alert">
                        <strong>@lang('order.payment.test_banner')</strong>
                    </div>

                    <h1 class="h3">@lang('order.payment.test_title')</h1>

                    <p class="text-muted">@lang('order.payment.test_intro')</p>

                    <dl class="row">
                        <dt class="col-sm-3">@lang('order.reference')</dt>
                        <dd class="col-sm-9 font-mono" dir="ltr">{{ $order->reference }}</dd>

                        <dt class="col-sm-3">@lang('order.amount')</dt>
                        <dd class="col-sm-9" dir="ltr">{{ $order->formattedTotal() }}</dd>

                        <dt class="col-sm-3">@lang('order.status')</dt>
                        <dd class="col-sm-9">{{ $order->status->label($currentLocale->value) }}</dd>
                    </dl>

                    <div class="d-flex gap-2 flex-wrap mt-4">
                        <form method="POST" action="{{ $callbackUrl }}">
                            @foreach ($approved as $name => $value)
                                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                            @endforeach
                            <button type="submit" class="btn btn-success">
                                @lang('order.payment.test_approve')
                            </button>
                        </form>

                        <form method="POST" action="{{ $callbackUrl }}">
                            @foreach ($declined as $name => $value)
                                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                            @endforeach
                            <button type="submit" class="btn btn-outline-danger">
                                @lang('order.payment.test_decline')
                            </button>
                        </form>
                    </div>

                    <p class="text-muted small mt-4">
                        @lang('order.payment.test_note')
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection