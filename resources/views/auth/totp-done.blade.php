{{--
    Enrolment finished.

    Its own URL rather than a redirect target with a flash message, so a reload
    does not bounce the visitor back to a form they no longer need, and so the
    state of this account has an address that can be bookmarked.
--}}
@extends('layouts.app')

@section('title', __('totp.done_title'))

@section('content')
    <div class="section-padding-04">
        <div class="container">
            <div class="app-shell app-shell--narrow app-shell--center">

                <span class="app-tick">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                    </svg>
                </span>

                <h1 class="app-title app-title--sm mt-4">@lang('totp.done_heading')</h1>

                <p class="app-lede">@lang('totp.done_lede')</p>

                <p class="app-note">
                    @lang('totp.recovery.remaining', ['count' => $remaining])
                </p>

                {{-- Ordering is the only thing enrolment unlocks, so it is the
                     action offered here rather than a generic dashboard link. --}}
                @if ($canOrder)
                    <a href="{{ route('pricing') }}" class="btn btn-primary">@lang('pricing.title')</a>
                @else
                    <a href="{{ route('account') }}" class="btn btn-primary">@lang('account.title')</a>
                @endif

                <p class="app-note mt-4">
                    <a href="{{ route('account') }}" class="app-link">@lang('account.title')</a>
                </p>
            </div>
        </div>
    </div>
@endsection
