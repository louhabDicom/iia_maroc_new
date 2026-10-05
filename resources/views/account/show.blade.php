@extends('layouts.app')

@section('title', __('account.title'))

@section('content')
    <x-front.hero
        :title="__('account.title')"
        :crumbs="[__('account.title') => null]"
        image="assets/images/bg/about_page_bg.jpg" />

    <div class="section-padding-04">
        <div class="container">
            <div class="app-shell">

                {{-- The verification state is stated first and linked, because it
                     is the one thing that blocks everything else. --}}
                <div class="app-card">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <p class="app-title app-title--sm mb-1">{{ $user->displayName() }}</p>
                            {{-- dir="ltr": both the email and the phone are
                                 west-to-west strings and would be reordered on
                                 the Arabic page. --}}
                            <p dir="ltr" class="app-note text-start">
                                {{ $user->email }}
                                @if ($user->phone)
                                    &middot; {{ $user->maskedPhone() }}
                                @endif
                            </p>
                        </div>

                        {{-- Profile editing. A link rather than a second form on this page: this is a
                         dashboard, and a thirteen-field edit form in the middle of
                         it pushes the orders — the thing most visitors came for —
                         below the fold. --}}
                        <a href="{{ route('account.edit') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-user-pen" aria-hidden="true"></i>
                            @lang('account.edit_title')
                        </a>

                        @if ($user->hasConfirmedTotp())
                            <span class="app-badge app-badge--success">@lang('account.totp_enrolled')</span>
                        @else
                            {{-- A button, not a badge: this is the one thing left to
                                 do before the account can order, so it has to be
                                 actionable from the page they land on. --}}
                            <a href="{{ route('totp.setup') }}" class="btn btn-primary">
                                @lang('account.totp_not_enrolled')
                            </a>
                        @endif
                    </div>

                    @if ($membership)
                        <p class="app-note mt-3 pt-3 border-top">
                            @lang('pricing.member'):
                            <span class="app-note--strong">{{ $membership->status->label($currentLocale->value) }}</span>
                        </p>
                    @endif
                </div>

                @unless ($canOrder)
                    <div class="app-notice app-notice--warning mt-4">
                        @lang('totp.pending_notice')
                    </div>
                @endunless

                <section class="app-section" aria-labelledby="orders-heading">
                    <h2 id="orders-heading" class="app-section__title">@lang('account.orders')</h2>

                    @if ($orders->isEmpty())
                        {{-- `order.no_orders` rather than the generic `state.empty`:
                             "no information is available yet" is a fallback for a
                             whole page, not for a section that is legitimately
                             empty. --}}
                        <div class="app-empty">
                            <p class="mb-3">@lang('order.no_orders')</p>
                            <a href="{{ route('pricing') }}" class="btn-join">@lang('pricing.register')</a>
                        </div>
                    @else
                        <ul class="app-list">
                            @foreach ($orders as $order)
                                <li class="app-list__row">
                                    <div>
                                        {{-- dir="ltr": an invoice reference is a
                                             code, not prose, and must not be
                                             bidi-reordered. --}}
                                        <p dir="ltr" class="app-note--strong text-start font-monospace mb-1">
                                            {{ $order->reference ?? ('#'.$order->getKey()) }}
                                        </p>
                                        <p class="app-note">{{ $order->created_at->translatedFormat('d M Y') }}</p>
                                    </div>

                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        {{-- `label()` on the enum rather than
                                             __('order.status.'.$order->status->value):
                                             a case with no label would otherwise
                                             render the raw key in the customer's
                                             order history. --}}
                                        <span class="app-badge app-badge--{{ $order->status->colour() }}">
                                            {{ $order->status->label($currentLocale->value) }}
                                        </span>

                                        <span dir="ltr" class="app-note--strong">
                                            {{-- `total` is the snapshot column
                                                 written at checkout. --}}
                                            {{ \App\Support\Money::format($order->total, $order->currency, $currentLocale->value) }}
                                        </span>

                                        <a href="{{ route('orders.show', ['order' => $order->id]) }}"
                                           class="btn btn-sm btn-outline-secondary">@lang('action.view')</a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <form method="POST" action="{{ route('logout') }}" class="mt-5 text-center">
                    @csrf
                    <button type="submit" class="btn btn-link app-link p-0">@lang('action.logout')</button>
                </form>
            </div>
        </div>
    </div>
@endsection
