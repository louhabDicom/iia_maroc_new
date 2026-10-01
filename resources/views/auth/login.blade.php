@extends('layouts.app')

@section('title', __('login.title'))

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">@lang('login.title')</h1>

        <form method="POST" action="{{ route('login.store') }}"
              class="mt-8 space-y-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            @csrf

            <x-form.field name="identifier" :label="__('login.identifier')" required>
                {{-- dir="ltr": the value may be an email address or a phone
                     number, and neither should be bidi-reordered on an Arabic
                     page. --}}
                <input type="text" name="identifier" id="identifier" required
                       autocomplete="username"
                       dir="ltr"
                       value="{{ old('identifier') }}"
                       @class(['input', 'text-start', 'input-error' => $errors->has('identifier')])
                       @if ($errors->has('identifier')) aria-invalid="true" aria-describedby="identifier-error" @endif>
            </x-form.field>

            <x-form.field name="password" :label="__('register.password')" required>
                <input type="password" name="password" id="password" required
                       autocomplete="current-password"
                       dir="ltr"
                       @class(['input', 'input-error' => $errors->has('password')])
                       @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
            </x-form.field>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="remember" id="remember" value="1"
                       @checked(old('remember'))
                       class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand">
                <label for="remember" class="text-sm text-slate-600 dark:text-slate-400">
                    @lang('action.login')
                </label>
            </div>

            <button type="submit" class="btn btn-primary w-full">@lang('login.submit')</button>

            <p class="text-sm text-slate-600 dark:text-slate-400">
                @lang('login.no_account')
                <a href="{{ route('register') }}" class="font-medium text-brand hover:underline">@lang('login.register_link')</a>
            </p>
        </form>
    </div>
@endsection
