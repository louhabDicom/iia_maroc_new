{{--
    The recovery codes, shown exactly once.

    This is the only response in the whole flow where these exist as plain text.
    Afterwards they are hashes, so a reload cannot show them again — which is
    the point, and is why the empty state below says so plainly instead of
    rendering a blank list that would read as "you have no codes".
--}}
@extends('layouts.app')

@section('title', __('totp.recovery.title'))

@section('content')
    <div class="section-padding-04">
        <div class="container">
            <div class="app-shell app-shell--narrow">

                <h1 class="app-title">@lang('totp.recovery.heading')</h1>

                @if ($codes === null)
                    <div class="app-notice app-notice--warning" role="alert">
                        @lang('totp.recovery.already_shown', ['count' => $remaining])
                    </div>

                    <p class="app-note mt-4 text-center">
                        <a href="{{ route('totp.done') }}" class="app-link">@lang('totp.recovery.continue')</a>
                    </p>
                @else
                    <p class="app-lede">@lang('totp.recovery.lede')</p>

                    <ul class="totp-codes" dir="ltr">
                        @foreach ($codes as $code)
                            <li class="totp-code">{{ $code }}</li>
                        @endforeach
                    </ul>

                    <p class="app-note mt-4">@lang('totp.recovery.warning')</p>

                    <a href="{{ route('totp.done') }}" class="btn btn-primary w-100 mt-4">
                        @lang('totp.recovery.continue')
                    </a>
                @endif
            </div>
        </div>
    </div>
@endsection
