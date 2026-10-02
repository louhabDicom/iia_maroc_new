{{--
    The code entry step.

    The number is shown masked, not in full: this is a public-facing form on a
    shared or public machine, and there is no reason to print somebody's number
    on it. The person still recognises their own number from the last two
    groups, and the "use a different number" path exists for when they do not.

    The code input is a single numeric field rather than six boxes. Six separate
    inputs are a common pattern and a common accessibility failure: past code
    boxes to the next field, prevent paste, and lose the value entirely when
    autofill fills the whole string into the first box.
--}}
@extends('layouts.app')

@section('title', __('verify.title'))

@section('content')
    <div class="section-padding-04">
        <div class="container">
            <div class="app-shell app-shell--narrow">

                @if (session('otp_unavailable'))
                    {{-- A delivery failure is stated before the form, not after
                         it: the visitor is about to wait for a code that is not
                         coming, and telling them afterwards wastes the wait. --}}
                    <div class="app-notice app-notice--warning mb-4" role="alert">
                        @lang('otp.delivery_failed')
                    </div>
                @endif

                <h1 class="app-title">@lang('verify.heading')</h1>

                <p class="app-lede">
                    @lang('verify.instructions')
                    {{-- Force LTR: the masked number is digits and separators, and
                         bidi reordering would present it in reverse on an Arabic
                         page. --}}
                    <strong dir="ltr" class="app-note--strong">{{ $maskedPhone }}</strong>
                </p>

                <div class="app-card">
                    <form method="POST" action="{{ route('verification.verify') }}">
                        @csrf

                        <x-form.field name="code" :label="__('verify.code_label')" :help="__('verify.code_hint')" required>
                            {{-- One field, not six boxes. Six separate inputs are
                                 a common pattern and a common accessibility
                                 failure: paste-to-advance, blocked paste, and
                                 the whole value lost when autofill fills the
                                 first box. --}}
                            <input type="text"
                                   name="code"
                                   id="code"
                                   required
                                   inputmode="numeric"
                                   autocomplete="one-time-code"
                                   pattern="\d{ {{ $codeLength }} }"
                                   maxlength="{{ $codeLength }}"
                                   dir="ltr"
                                   {{-- autofocus only on the first paint; a field
                                        that grabs focus on every validation
                                        re-render loses what the visitor typed. --}}
                                   @if (! $errors->any()) autofocus @endif
                                   @class(['input', 'app-code', 'input-error' => $errors->has('code')])
                                   @if ($errors->has('code')) aria-invalid="true" aria-describedby="code-error" @endif>
                        </x-form.field>

                        @if ($attemptsLeft !== null && $attemptsLeft <= 2)
                            <div class="app-notice app-notice--warning mb-4">
                                @lang('verify.attempts_left', ['count' => $attemptsLeft])
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary w-100">@lang('verify.submit')</button>
                    </form>

                    {{-- Resend is a separate POST rather than a link, so it cannot
                         be triggered by a prefetch or a crawler. --}}
                    <form method="POST" action="{{ route('verification.resend') }}" class="mt-4 text-center">
                        @csrf
                        <button type="submit" class="btn btn-link app-link p-0">
                            @lang('verify.resend')
                        </button>
                    </form>
                </div>

                <p class="app-note mt-4 text-center">
                    <a href="{{ route('account') }}" class="app-link">@lang('verify.change_number')</a>
                </p>
            </div>
        </div>
    </div>
@endsection
