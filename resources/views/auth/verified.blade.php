@extends('layouts.app')

@section('title', __('account.phone_verified'))

@section('content')
    <div class="section-padding-04">
        <div class="container">
            <div class="app-shell app-shell--narrow app-shell--center">

                <span class="app-tick">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                    </svg>
                </span>

                <h1 class="app-title app-title--sm mt-4">@lang('account.phone_verified')</h1>

                <p class="app-lede">@lang('verify.success')</p>

                {{-- Ordering is the only thing verification unlocks, so it is
                     the action offered here rather than a generic dashboard
                     link. --}}
                <a href="{{ route('pricing') }}" class="btn btn-primary">@lang('pricing.title')</a>

                <p class="app-note mt-4">
                    <a href="{{ route('account') }}" class="app-link">@lang('account.title')</a>
                </p>
            </div>
        </div>
    </div>
@endsection
