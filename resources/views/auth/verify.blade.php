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
    <div class="mx-auto max-w-md">

        @if (session('otp_unavailable'))
            <div role="alert"
                 class="mb-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                @lang('otp.delivery_failed')
            </div>
        @endif

        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
            @lang('verify.heading')
        </h1>

        <p class="mt-3 text-slate-600 dark:text-slate-400">
            @lang('verify.instructions')
            {{-- Force LTR: the masked number is digits and separators, and bidi
                 reordering would present it in reverse on an Arabic page. --}}
            <strong dir="ltr" class="text-slate-900 dark:text-white">{{ $maskedPhone }}</strong>
        </p>

        <form method="POST" action="{{ route('verification.verify') }}"
              class="mt-8 space-y-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            @csrf

            <x-form.field name="code" :label="__('verify.code_label')" :help="__('verify.code_hint')" required>
                <input type="text"
                       name="code"
                       id="code"
                       required
                       inputmode="numeric"
                       autocomplete="one-time-code"
                       pattern="\d{ {{ $codeLength }} }"
                       maxlength="{{ $codeLength }}"
                       dir="ltr"
                       {{-- autofocus only on the first paint; a field that grabs
                            focus on every validation re-render loses what the
                            visitor already typed. --}}
                       @if (! $errors->any()) autofocus @endif
                       @class(['input text-center font-mono text-2xl tracking-[0.5em]', 'input-error' => $errors->has('code')])
                       @if ($errors->has('code')) aria-invalid="true" aria-describedby="code-error" @endif>
            </x-form.field>

            @if ($attemptsLeft !== null && $attemptsLeft <= 2)
                <p class="text-sm font-medium text-amber-700 dark:text-amber-400">
                    @lang('verify.attempts_left', ['count' => $attemptsLeft])
                </p>
            @endif

            <button type="submit" class="btn btn-primary w-full">@lang('verify.submit')</button>
        </form>

        {{-- Resend is a separate POST rather than a link, so it cannot be
             triggered by a prefetch or a crawler. --}}
        <form method="POST" action="{{ route('verification.resend') }}" class="mt-4 text-center">
            @csrf
            <button type="submit" class="text-sm font-medium text-brand hover:underline">
                @lang('verify.resend')
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
            <a href="{{ route('account') }}" class="hover:underline">@lang('verify.change_number')</a>
        </p>
    </div>
@endsection
