@extends('layouts.app')

@section('title', __('order.title'))

@section('content')


    {{-- Checkout.

         The form and the summary are separated bands of the same rhythm as every
         other page rather than a bespoke two-column layout: the delegate reads
         this on a phone at 23:00 the night before, and the page they already know
         from the rest of the site is one less thing to learn. --}}
    <section class="ux-section section-padding-03 ux-section--defer"
             aria-labelledby="checkout-heading">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-8 ux-reveal ux-reveal-left">

                    <x-section-head
                        id="checkout-heading"
                        :eyebrow="__('order.title')"
                        :title="__('order.billing_details')"
                        :level="2"
                        class="mb-4" />

                    <form method="POST" action="{{ route('checkout.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <x-form.field name="first_name" :label="__('register.first_name')" required>
                                    <input type="text" name="first_name" id="first_name" required
                                           value="{{ old('first_name', $user->first_name) }}"
                                           class="form-control @error('first_name') is-invalid @enderror">
                                </x-form.field>
                            </div>

                            <div class="col-md-6">
                                <x-form.field name="last_name" :label="__('register.last_name')" required>
                                    <input type="text" name="last_name" id="last_name" required
                                           value="{{ old('last_name', $user->last_name) }}"
                                           class="form-control @error('last_name') is-invalid @enderror">
                                </x-form.field>
                            </div>

                            <div class="col-md-6">
                                <x-form.field name="organisation" :label="__('register.organisation')">
                                    <input type="text" name="organisation" id="organisation"
                                           value="{{ old('organisation', $user->organisation) }}"
                                           class="form-control">
                                </x-form.field>
                            </div>

                            <div class="col-md-6">
                                <x-form.field name="email" :label="__('register.email')" required>
                                    <input type="email" name="email" id="email" required dir="ltr"
                                           value="{{ old('email', $user->email) }}"
                                           class="form-control @error('email') is-invalid @enderror">
                                </x-form.field>
                            </div>

                            <div class="col-md-6">
                                <x-form.field name="phone" :label="__('register.phone')" required>
                                    <input type="tel" name="phone" id="phone" required dir="ltr"
                                           value="{{ old('phone', $user->phone) }}"
                                           class="form-control @error('phone') is-invalid @enderror">
                                </x-form.field>
                            </div>

                            <div class="col-md-6">
                                <x-form.field name="address" :label="__('order.address')">
                                    <input type="text" name="address" id="address"
                                           value="{{ old('address') }}" class="form-control">
                                </x-form.field>
                            </div>

                            <div class="col-md-6">
                                <x-form.field name="city" :label="__('register.city')">
                                    <input type="text" name="city" id="city"
                                           value="{{ old('city') }}" class="form-control">
                                </x-form.field>
                            </div>
                        </div>

                        <x-section-head
                            :title="__('order.participants')"
                            :level="2"
                            class="mt-5 mb-3" />

                        <p class="text-muted">@lang('order.participant_help')</p>

                        {{-- The refusal slot. `role="alert"` because a refusal has to
                             be announced: with scripting on it arrives without a page
                             load, and a message that only appears is a message a
                             screen-reader user is never told about. --}}
                        <div data-seats-error>
                            @error('participants')
                                <p class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div data-seats>
                            @include('pages.checkout.seats', [
                                'seatRows' => $seatRows,
                                'quote' => $quote,
                            ])
                        </div>

                        {{-- One form block per place, not a JavaScript-added
                             row. The 2024 form built its participant rows in
                             JS, so a visitor with scripting disabled — or a
                             screenshot pasted into a message — produced a
                             silently empty registration. --}}
                        <div data-participants>
                            @include('pages.checkout.participants', [
                                'quote' => $quote,
                                'participantValues' => $participantValues,
                            ])
                        </div>

                        <button type="submit" class="btn-join btn-cirle mt-3" style="color: wheat !important;">
                            @lang('order.place_order')
                        </button>


                    </form>
                </div>
                {{-- The summary keeps the table: it really is two columns of
                     numbers that line up, and that is what a table is for. What
                     changes is the frame — the legacy price-card is a Bootstrap
                     panel, and the rest of the site now speaks in ux-card. --}}
                <div class="col-lg-4 ux-reveal ux-reveal-right">
                    <div class="ux-card ux-card--glass ux-radius-xl p-4 text-center">
                        <h2 class="h5 ux-card__title">@lang('order.summary')</h2>

                        <div data-summary>
                            @include('pages.checkout.summary', [
                                'quote' => $quote,
                                'currentLocale' => $currentLocale,
                            ])
                        </div>

                        <p class="ux-ink-soft small">@lang('order.payment.secure')</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
