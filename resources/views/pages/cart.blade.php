@extends('layouts.app')

@section('title', __('order.cart.title'))
@section('description', __('order.cart.subtitle'))

@section('content')

    <x-page-hero
        :title="__('order.cart.title')"
        :crumbs="[__('order.cart.title') => null]"
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
                    <table class="table align-middle">
                        <caption class="visually-hidden">@lang('order.cart.title')</caption>
                        <thead>
                            <tr>
                                <th scope="col">@lang('order.ticket')</th>
                                <th scope="col" class="text-center">@lang('order.quantity')</th>
                                <th scope="col" class="text-center">@lang('order.member_places')</th>
                                <th scope="col" class="text-end">@lang('order.amount')</th>
                                <th scope="col"><span class="visually-hidden">@lang('action.remove')</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cart->items as $item)
                                <tr>
                                    <th scope="row" class="fw-normal">
                                        {{ $item->ticketType?->name ?? __('order.ticket') }}
                                        <div class="small text-muted">{{ $item->ticketType?->description }}</div>
                                    </th>

                                    <td class="text-center">
                                        {{-- One form per line rather than one
                                             form for the table, so a validation
                                             error on one line cannot silently
                                             discard edits made to another. --}}
                                        <form method="POST"
                                              action="{{ route('cart.update', ['cart' => $cart->id, 'ticketType' => $item->ticket_type_id]) }}"
                                              class="d-inline-flex align-items-center gap-2">
                                            @csrf
                                            <label class="visually-hidden" for="qty-{{ $item->id }}">@lang('order.quantity')</label>
                                            <input type="number" id="qty-{{ $item->id }}" name="quantity"
                                                   value="{{ $item->quantity }}" min="0" max="20"
                                                   class="form-control form-control-sm" style="width: 5rem;">
                                            <input type="hidden" name="member_quantity" value="{{ $item->member_quantity }}">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">@lang('action.update')</button>
                                        </form>
                                    </td>

                                    <td class="text-center">{{ $item->member_quantity }}</td>

                                    <td class="text-end">
                                        {{-- dir="ltr": an amount next to a
                                             currency code must not be
                                             bidi-reordered on the Arabic page. --}}
                                        <span dir="ltr">{{ $item->formattedEstimatedTotal() }}</span>
                                    </td>

                                    <td class="text-end">
                                        <form method="POST"
                                              action="{{ route('cart.destroy', ['cart' => $cart->id, 'ticketType' => $item->ticket_type_id]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-link text-danger">@lang('action.remove')</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th scope="row" colspan="3" class="text-end">@lang('order.cart.estimate')</th>
                                <td class="text-end">
                                    <span dir="ltr" class="fw-bold">{{ \App\Support\Money::format($estimatedTotal, $currency) }}</span>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>

                    {{-- The estimate is explicitly labelled as one. The
                         authoritative figure is the quote shown at checkout,
                         because that is where the member rate is applied from
                         the server's own membership record rather than from
                         what the visitor claimed. --}}
                    <p class="text-muted small">@lang('order.cart.estimate_note')</p>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('pricing') }}" class="btn btn-outline-secondary">@lang('order.cart.continue')</a>
                        <form method="POST" action="{{ route('cart.clear') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger">@lang('order.cart.clear')</button>
                        </form>
                    </div>

                </div>
                <div class="col-lg-4">
                    <div class="price-card text-center">
                        <div class="price-body">
                            <h2 class="h5">@lang('order.summary')</h2>

                            <p class="text-muted">@lang('order.cart.summary_note')</p>

                            {{-- Both paths lead to the same place. Signed out,
                                 the checkout sends the visitor to sign in and
                                 keeps the basket, so they come back to it. --}}
                            <a href="{{ auth()->check() ? route('checkout') : route('login') }}"
                               class="btn-join w-100 text-center d-block">@lang('order.cart.checkout')</a>

                            @guest
                                <p class="text-muted small mt-2">@lang('order.cart.sign_in_note')</p>
                            @endguest
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
