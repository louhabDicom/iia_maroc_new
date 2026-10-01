@extends('layouts.app')

@section('title', __('account.title'))

@section('content')
    <div class="mx-auto max-w-4xl">

        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
            @lang('account.title')
        </h1>

        {{-- The verification state is stated first and linked, because it is the
             one thing that blocks everything else. --}}
        <div class="mt-6 rounded-lg border border-slate-200 p-5 dark:border-slate-800">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="font-medium text-slate-900 dark:text-white">{{ $user->displayName() }}</p>
                    {{-- dir="ltr": both the email and the phone are west-to-west
                         strings and would be reordered on the Arabic page. --}}
                    <p dir="ltr" class="text-start text-sm text-slate-600 dark:text-slate-400">
                        {{ $user->email }}
                        @if ($user->phone)
                            &middot; {{ $user->maskedPhone() }}
                        @endif
                    </p>
                </div>

                @if ($user->hasVerifiedPhone())
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-sm text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                        @lang('account.phone_verified')
                    </span>
                @else
                    <a href="{{ route('verification.notice') }}" class="btn btn-primary">
                        @lang('account.phone_unverified')
                    </a>
                @endif
            </div>

            @if ($membership)
                <p class="mt-4 border-t border-slate-200 pt-4 text-sm dark:border-slate-800">
                    @lang('pricing.member'):
                    <span class="font-medium">{{ $membership->status->label($currentLocale->value) }}</span>
                </p>
            @endif
        </div>

        @unless ($canOrder)
            <p class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                @lang('verify.pending_notice')
            </p>
        @endunless

        <section class="mt-10" aria-labelledby="orders-heading">
            <h2 id="orders-heading" class="text-xl font-semibold text-slate-900 dark:text-white">
                @lang('account.orders')
            </h2>

            @if ($orders->isEmpty())
                {{-- `order.no_orders` rather than the generic `state.empty`:
                     "no information is available yet" is a fallback for a whole
                     page, not for a section that is legitimately empty. --}}
                <p class="mt-4 text-slate-600 dark:text-slate-400">@lang('order.no_orders')</p>
            @else
                <ul class="mt-4 divide-y divide-slate-200 dark:divide-slate-800">
                    @foreach ($orders as $order)
                        <li class="flex flex-wrap items-center justify-between gap-4 py-4">
                            <div>
                                {{-- dir="ltr": an invoice reference is a code, not
                                     prose, and must not be bidi-reordered. --}}
                                <p dir="ltr" class="text-start font-mono text-sm text-slate-900 dark:text-white">
                                    {{ $order->reference ?? ('#'.$order->getKey()) }}
                                </p>
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    {{ $order->created_at->translatedFormat('d M Y') }}
                                </p>
                            </div>

                            <div class="flex items-center gap-4">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{-- `label()` on the enum rather than
                                         __('order.status.'.$order->status->value):
                                         a case with no label would otherwise
                                         render the raw key in the customer's
                                         order history. --}}
                                    {{ $order->status->label($currentLocale->value) }}
                                </span>
                                <span dir="ltr" class="text-start font-medium text-slate-900 dark:text-white">
                                    {{-- `total` is the snapshot column written at
                                         checkout. There is no `total_amount`. --}}
                                    {{ \App\Support\Money::format($order->total, $order->currency, $currentLocale->value) }}
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <form method="POST" action="{{ route('logout') }}" class="mt-12">
            @csrf
            <button type="submit" class="text-sm font-medium text-rose-600 hover:underline">
                @lang('action.logout')
            </button>
        </form>
    </div>
@endsection
