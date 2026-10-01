@extends('layouts.app')

@section('title', __('account.phone_verified'))

@section('content')
    <div class="mx-auto max-w-md text-center">

        <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
            <svg class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
            </svg>
        </span>

        <h1 class="mt-6 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
            @lang('account.phone_verified')
        </h1>

        <p class="mt-2 text-slate-600 dark:text-slate-400">@lang('verify.success')</p>

        <div class="mt-8">
            {{-- Ordering is the only thing the verification unlocks, so this is
                 the next step rather than a generic dashboard link. --}}
            <a href="{{ route('pricing') }}" class="btn btn-primary">@lang('pricing.title')</a>
        </div>

        <p class="mt-4 text-sm">
            <a href="{{ route('account') }}" class="text-brand hover:underline">@lang('account.title')</a>
        </p>
    </div>
@endsection
