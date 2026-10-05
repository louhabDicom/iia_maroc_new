{{--
    The profile page.

    Two forms, not one. The details form and the password form are separate acts
    with separate risks and separate consequences, and a delegate who came to
    fix a typo in their phone number should not have a "change my password"
    button sitting next to it. Splitting them also means a failed password
    change cannot discard the edits just made to the identity fields.

    The password form requires the current password. That is the reason it is
    worth the extra field: without it, anyone who can reach an unlocked session
    can lock the owner out of an account that already holds paid orders, and
    recovery is an email round trip.
--}}
@extends('layouts.app')

@section('title', __('account.edit_title'))
@section('description', __('account.edit_lede'))

@section('content')

<div class="d-page d-page--account">

    <x-front.hero
        :title="__('account.edit_title')"
        :eyebrow="__('account.title')"
        :lede="__('account.edit_lede')"
        :crumbs="[
            __('account.title') => route('account'),
            __('account.profile') => null,
        ]"
        :facts="[
            ['icon' => 'fa-shield-alt', 'label' => $user->hasConfirmedTotp()
                ? __('account.totp_enrolled')
                : __('account.totp_not_enrolled')],
        ]"
        :cta-label="__('account.title')"
        :cta-url="route('account')"
        :secondary-label="__('nav.pricing')"
        :secondary-url="route('pricing')"
        image="assets/images/bg/about_page_bg.jpg" />

    <section class="d-section" aria-labelledby="details-heading">
        <div class="container">

            {{-- Above both forms rather than inside either one. A delegate who
                 mistypes their current password needs to see why before they
                 start looking for a field they did not get wrong, and a notice
                 sitting under the password form is a full screen away from the
                 top on a phone. --}}
            @if (session('status') || session('email_status') || $errors->any())
                <div class="d-notices mb-5">
                    @foreach (['status', 'email_status'] as $flash)
                        @if (session($flash))
                            <p class="d-notice d-notice--success" role="status">
                                <i class="fas fa-check-circle" aria-hidden="true"></i>
                                <span>{{ session($flash) }}</span>
                            </p>
                        @endif
                    @endforeach

                    @if ($errors->any())
                        <div class="d-notice d-notice--danger" role="alert">
                            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                            <ul>
                                @foreach ($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif

            <div class="row g-4 g-xl-5">

                {{-- -----------------------------------------------------------------
                    The details form. Left column.
                ----------------------------------------------------------------- --}}
                <div class="col-lg-7 ux-reveal ux-reveal-left">
                    <div class="d-form-panel d-form">
                        <h2 id="details-heading" class="d-form-panel__title">
                            @lang('account.edit_details')
                        </h2>

                        <p class="d-form-panel__lede">@lang('account.edit_details_lede')</p>

                        <form method="POST" action="{{ route('account.update') }}">
                            @csrf

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <x-form.field name="first_name"
                                                  :label="__('register.first_name')" required>
                                        <input type="text" name="first_name" id="first_name" required
                                               autocomplete="given-name"
                                               value="{{ old('first_name', $user->first_name) }}"
                                               @class(['comment-form-input', 'is-invalid' => $errors->has('first_name')])>
                                    </x-form.field>
                                </div>

                                <div class="col-md-6">
                                    <x-form.field name="last_name"
                                                  :label="__('register.last_name')" required>
                                        <input type="text" name="last_name" id="last_name" required
                                               autocomplete="family-name"
                                               value="{{ old('last_name', $user->last_name) }}"
                                               @class(['comment-form-input', 'is-invalid' => $errors->has('last_name')])>
                                    </x-form.field>
                                </div>

                                <div class="col-12">
                                    <x-form.field name="email" :label="__('register.email')" required>
                                        <input type="email" name="email" id="email" required
                                               autocomplete="email"
                                               dir="ltr"
                                               value="{{ old('email', $user->email) }}"
                                               @class(['comment-form-input', 'is-invalid' => $errors->has('email')])>

                                        {{-- The confirmation state is stated rather than
                                             left for the delegate to infer: an address
                                             that was never confirmed is why their
                                             order cannot proceed, and it is not
                                             visible anywhere else. --}}
                                        @if ($user->hasVerifiedEmail())
                                            <p class="d-field__ok">
                                                <i class="fas fa-check-circle" aria-hidden="true"></i>
                                                <span>@lang('account.email_confirmed')</span>
                                            </p>
                                        @else
                                            <p class="d-field__warn">
                                                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                                                <span>@lang('account.email_pending')</span>
                                            </p>
                                        @endif
                                    </x-form.field>
                                </div>

                                <div class="col-12">
                                    <x-form.field name="phone" :label="__('register.phone')" required>
                                        <input type="tel" name="phone" id="phone" required
                                               autocomplete="tel"
                                               dir="ltr"
                                               value="{{ old('phone', $user->phone) }}"
                                               @class(['comment-form-input', 'is-invalid' => $errors->has('phone')])>
                                    </x-form.field>
                                </div>

                                <div class="col-md-6">
                                    <x-form.field name="organisation"
                                                  :label="__('register.organisation')">
                                        <input type="text" name="organisation" id="organisation"
                                               autocomplete="organization"
                                               value="{{ old('organisation', $user->organisation) }}"
                                               class="comment-form-input">
                                    </x-form.field>
                                </div>

                                <div class="col-md-6">
                                    <x-form.field name="job_title"
                                                  :label="__('register.job_title')">
                                        <input type="text" name="job_title" id="job_title"
                                               autocomplete="organization-title"
                                               value="{{ old('job_title', $user->job_title) }}"
                                               class="comment-form-input">
                                    </x-form.field>
                                </div>

                                <div class="col-md-6">
                                    <x-form.field name="city" :label="__('register.city')">
                                        <input type="text" name="city" id="city"
                                               autocomplete="address-level2"
                                               value="{{ old('city', $user->city) }}"
                                               class="comment-form-input">
                                    </x-form.field>
                                </div>

                                <div class="col-md-6">
                                    <x-form.field name="country_id"
                                                  :label="__('register.country')"
                                                  :help="__('register.country_help')">
                                        <select name="country_id" id="country_id"
                                                class="comment-form-input">
                                            <option value="">—</option>
                                            @foreach ($countries as $country)
                                                <option value="{{ $country->id }}"
                                                    @selected((int) old('country_id', $user->country_id) === (int) $country->id)>
                                                    {{ $country->name() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </x-form.field>
                                </div>

                                <div class="col-12">
                                    <x-form.field name="locale"
                                                  :label="__('register.language')"
                                                  :help="__('register.language_help')">
                                        {{-- A radio group, not a select: there are three
                                             options, they are all visible at once, and
                                             a select would hide the current choice
                                             behind a click on a page whose whole
                                             point is choosing. The labels are the
                                             languages' own names rather than
                                             "Français / English / العربية" in the
                                             reader's language, because someone
                                             changing their reading language may not
                                             yet read the current one fluently. --}}
                                        <div class="d-choice" role="radiogroup"
                                             aria-label="{{ __('register.language') }}">
                                            @foreach (['fr' => 'Français',
                                                       'en' => 'English',
                                                       'ar' => 'العربية'] as $code => $label)
                                                <label class="d-choice__option">
                                                    <input type="radio" name="locale" value="{{ $code }}"
                                                           @checked(old('locale', $user->locale ?? app()->getLocale()) === $code)>
                                                    <span lang="{{ $code }}">{{ $label }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </x-form.field>
                                </div>

                                <div class="col-12 d-flex flex-wrap align-items-center gap-3">
                                    <button type="submit" class="d-btn d-btn--primary">
                                        <span>@lang('account.edit_save')</span>
                                    </button>

                                    <a href="{{ route('account') }}" class="d-btn d-btn--ghost">
                                        @lang('action.cancel')
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- -----------------------------------------------------------------
                    The sidebar: the password form, and the state of the account.

                    It is the narrow column because it is the part a delegate reads
                    once and then acts on, not the part they scroll through.
                ----------------------------------------------------------------- --}}
                <div class="col-lg-5 ux-reveal ux-reveal-right">
                    <div class="d-stack">

                        <div class="d-form-panel d-form">
                            <h2 class="d-form-panel__title">@lang('account.edit_password')</h2>

                            <p class="d-form-panel__lede">@lang('account.edit_password_lede')</p>

                            <form method="POST" action="{{ route('account.password') }}">
                                @csrf

                                <div class="d-stack">
                                    <x-form.field name="current_password"
                                                  :label="__('account.current_password')" required>
                                        <input type="password" name="current_password"
                                               id="current_password" required
                                               autocomplete="current-password"
                                               dir="ltr"
                                               @class(['comment-form-input', 'is-invalid' => $errors->has('current_password')])>
                                    </x-form.field>

                                    <x-form.field name="password"
                                                  :label="__('account.new_password')"
                                                  :help="__('register.password_help')" required>
                                        <input type="password" name="password" id="password" required
                                               autocomplete="new-password"
                                               dir="ltr"
                                               @class(['comment-form-input', 'is-invalid' => $errors->has('password')])>
                                    </x-form.field>

                                    <x-form.field name="password_confirmation"
                                                  :label="__('account.confirm_password')" required>
                                        <input type="password" name="password_confirmation"
                                               id="password_confirmation" required
                                               autocomplete="new-password"
                                               dir="ltr"
                                               @class(['comment-form-input', 'is-invalid' => $errors->has('password')])>
                                    </x-form.field>

                                    <button type="submit" class="d-btn d-btn--primary w-100">
                                        <span>@lang('account.password_submit')</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="d-panel d-panel--quiet">
                            <h3 class="d-panel__title">@lang('account.title')</h3>

                            <ul class="d-panel__list">
                                <li class="d-panel__row">
                                    <span class="d-panel__key">@lang('account.totp_label')</span>

                                    @if ($user->hasConfirmedTotp())
                                        <span class="d-tag d-tag--success">@lang('account.totp_enrolled')</span>
                                    @else
                                        <a href="{{ route('totp.setup') }}" class="d-panel__link">
                                            @lang('account.totp_not_enrolled')
                                        </a>
                                    @endif
                                </li>

                                <li class="d-panel__row">
                                    <span class="d-panel__key">@lang('pricing.member')</span>

                                    @if ($user->isActiveMember())
                                        <span class="d-tag d-tag--success">@lang('pricing.member')</span>
                                    @else
                                        <span class="d-tag">@lang('pricing.standard')</span>
                                    @endif
                                </li>

                                <li class="d-panel__row">
                                    <span class="d-panel__key">@lang('account.orders')</span>

                                    <a href="{{ route('orders.index') }}" class="d-panel__link">
                                        @lang('action.view')
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>

@endsection