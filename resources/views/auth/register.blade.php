{{--
    Registration form.

    Notes on the markup that matter more than the styling:

    - `dir` on the phone field is forced to ltr. A phone number is a west-to-west
      string of digits, and inside an RTL paragraph the bidi algorithm will
      otherwise reorder the groups: "+212 6 12 34 56 78" renders as
      "78 56 34 12 6 212+" on an Arabic page, which people cannot read back.
    - The phone input is `inputmode="numeric"` and `autocomplete="tel"`, so a
      phone keyboard appears on mobile and a browser can fill it.
    - Every error is tied to its input with aria-describedby and
      aria-invalid, because a red border alone tells a screen-reader user
      nothing.
--}}
@extends('layouts.app')

@section('title', __('register.title'))
@section('description', __('register.subtitle'))

@section('content')
    {{-- A standalone form screen. The card and shell carry the layout, so the
         heading, the form and the closing link all sit in the same column as
         the sign-in and verification screens. --}}
    <div class="section-padding-04">
        <div class="container">
            <div class="app-shell">

        <h1 class="app-title">@lang('register.title')</h1>
        <p class="app-lede">@lang('register.subtitle')</p>

        <div class="app-card">
        <form method="POST" action="{{ route('register.store') }}">
            @csrf

            <div class="row g-4">
                <div class="col-md-6">
                <x-form.field name="first_name" :label="__('register.first_name')" required>
                    <input type="text" name="first_name" id="first_name" required
                           autocomplete="given-name"
                           value="{{ old('first_name') }}"
                           @class(['input', 'input-error' => $errors->has('first_name')])
                           @if ($errors->has('first_name')) aria-invalid="true" aria-describedby="first_name-error" @endif>
                </x-form.field>
                </div>

                <div class="col-md-6">
                <x-form.field name="last_name" :label="__('register.last_name')" required>
                    <input type="text" name="last_name" id="last_name" required
                           autocomplete="family-name"
                           value="{{ old('last_name') }}"
                           @class(['input', 'input-error' => $errors->has('last_name')])
                           @if ($errors->has('last_name')) aria-invalid="true" aria-describedby="last_name-error" @endif>
                </x-form.field>
                </div>
            </div>

            <x-form.field name="email" :label="__('register.email')" required>
                <input type="email" name="email" id="email" required
                       autocomplete="email"
                       dir="ltr"
                       placeholder="{{ __('register.email_placeholder') }}"
                       value="{{ old('email') }}"
                       @class(['input', 'input-error' => $errors->has('email')])
                       @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
            </x-form.field>

            <x-form.field name="phone" :label="__('register.phone')" :help="__('register.phone_help')" required>
                {{-- dir="ltr" and autocomplete="tel": see the note at the top of
                     this file. The bidi reordering is the single most common
                     Arabic-page defect in a phone form. --}}
                <input type="tel" name="phone" id="phone" required
                       inputmode="numeric"
                       autocomplete="tel"
                       dir="ltr"
                       placeholder="{{ __('register.phone_placeholder') }}"
                       value="{{ old('phone') }}"
                       @class(['input', 'text-start', 'input-error' => $errors->has('phone')])
                       @if ($errors->has('phone')) aria-invalid="true" aria-describedby="phone-error" @endif>
            </x-form.field>

            <div class="grid gap-6 sm:grid-cols-2">
                <x-form.field name="organisation" :label="__('register.organisation')">
                    <input type="text" name="organisation" id="organisation"
                           autocomplete="organization"
                           value="{{ old('organisation') }}"
                           class="input">
                </x-form.field>

                <x-form.field name="job_title" :label="__('register.job_title')">
                    <input type="text" name="job_title" id="job_title"
                           autocomplete="organization-title"
                           value="{{ old('job_title') }}"
                           class="input">
                </x-form.field>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                <x-form.field name="country_id" :label="__('register.country')">
                    <select name="country_id" id="country_id" class="input">
                        <option value="">@lang('misc.optional')</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}"
                                    @selected(old('country_id') == $country->id)>
                                {{ $country->name() }}
                            </option>
                        @endforeach
                    </select>
                </x-form.field>
                </div>

                <div class="col-md-6">
                <x-form.field name="city" :label="__('register.city')">
                    <input type="text" name="city" id="city"
                           autocomplete="address-level2"
                           value="{{ old('city') }}"
                           class="input">
                </x-form.field>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                <x-form.field name="password" :label="__('register.password')" :help="__('register.password_help')" required>
                    <input type="password" name="password" id="password" required
                           autocomplete="new-password"
                           dir="ltr"
                           @class(['input', 'input-error' => $errors->has('password')])
                           @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
                </x-form.field>
                </div>

                <div class="col-md-6">
                <x-form.field name="password_confirmation" :label="__('register.password_confirmation')" required>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                           autocomplete="new-password"
                           dir="ltr"
                           class="input">
                </x-form.field>
                </div>
            </div>

            {{-- Consent. `required` on the input as well as validated server-side,
                 so the error appears before the round trip and the server check
                 is the one that actually counts. --}}
            <div>
                <div class="app-check">
                    <input type="checkbox" name="terms" id="terms" value="1" required
                           @checked(old('terms'))
                           @class(['input-error' => $errors->has('terms')])
                           @if ($errors->has('terms')) aria-invalid="true" aria-describedby="terms-error" @endif>
                    <label for="terms">
                        @lang('register.terms')
                        <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                </div>
                @error('terms')
                    <p id="terms-error" class="field-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <p class="app-note">@lang('register.privacy')</p>

            <div class="d-flex flex-wrap align-items-center gap-4">
                <button type="submit" class="btn btn-primary">@lang('register.submit')</button>
                <p class="app-note">
                    @lang('register.have_account')
                    <a href="{{ route('login') }}" class="app-link">@lang('register.login_link')</a>
                </p>
            </div>
        </form>
        </div>
            </div>
        </div>
    </div>
@endsection
