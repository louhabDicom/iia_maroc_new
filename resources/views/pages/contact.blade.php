@extends('layouts.app')

@section('title', __('contact.title'))
@section('description', __('contact.intro'))

@section('content')

    <x-page-hero
        :title="__('contact.title')"
        :crumbs="[__('nav.contact') => null]"
        image="assets/images/bg/map_bg.png" />

    <div class="contact-form-section section-padding-03">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-10 text-center">
                    <h5 class="sub-title orange">@lang('contact.intro')</h5>
                    <h3 class="title mt-2">@lang('contact.title')</h3>
                </div>
            </div>

            <div class="row g-5 justify-content-between mt-1">
                <div class="section-title-wrap">
                    <div class="section-title">
                        <h2>@lang('contact.direct')</h2>
                    </div>
                </div>

                <div class="col-lg-5">
                    {{-- Direct details, from the edition row. Shown inside an
                         address element so a screen reader announces the block
                         correctly rather than as three loose strings. --}}
                    @if ($edition?->contact_email || $edition?->contact_phone || $edition?->venue_address)
                        <div class="single-contact">
                            <span class="contact-icon">
                                <img src="{{ asset('assets/images/icons/contact/localisation.png') }}"
                                     alt="" aria-hidden="true">
                            </span>
                            <div class="contact-info w-75">
                                <h4 class="contact-label">@lang('contact.office')</h4>
                                <p class="address">
                                    @if ($edition?->venue_address)
                                        {!! nl2br(e($edition->venue_address)) !!}
                                    @endif
                                    @if ($edition?->contact_phone)
                                        {{-- dir="ltr" so a number is not bidi-reordered on the
                                             Arabic page. --}}
                                        <span class="d-block mt-1" dir="ltr">{{ $edition->contact_phone }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif

                    <div class="single-contact">
                        <span class="contact-icon orange-color">
                            <img src="{{ asset('assets/images/icons/contact/phone.png') }}"
                                 alt="" aria-hidden="true">
                        </span>
                        <div class="contact-info w-75">
                            <h4 class="contact-label">@lang('contact.phone')</h4>
                            <p class="address" dir="ltr">
                                @if ($edition?->contact_phone)
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $edition->contact_phone) }}">
                                        {{ $edition->contact_phone }}
                                    </a>
                                @else
                                    <span class="text-muted">@lang('state.not_available')</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="single-contact">
                        <span class="contact-icon blue-color">
                            <img src="{{ asset('assets/images/icons/contact/email.png') }}"
                                 alt="" aria-hidden="true">
                        </span>
                        <div class="contact-info w-75">
                            <h4 class="contact-label">@lang('contact.email')</h4>
                            <p class="address" dir="ltr">
                                @if ($edition?->contact_email)
                                    <a href="mailto:{{ $edition->contact_email }}">{{ $edition->contact_email }}</a>
                                @else
                                    <span class="text-muted">@lang('state.not_available')</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="single-contact">
                        <span class="contact-icon blue-color">
                            <img src="{{ asset('assets/images/icons/contact/internet.png') }}"
                                 alt="" aria-hidden="true">
                        </span>
                        <div class="contact-info w-75">
                            <h4 class="contact-label">@lang('contact.website')</h4>
                            <p class="address" dir="ltr">
                                <a href="{{ route('home') }}">{{ parse_url(config('app.url'), PHP_URL_HOST) ?? 'www.arabcia.com' }}</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- The topic decides the delivery route, so it is a required
                 select rather than free text. The options are the active
                 `contact_routes` rows. --}}
            <div class="contact-form-wrap mt-4">
                <form method="POST" action="{{ route('contact.store') }}" class="contact-form">
                    @csrf

                    <div class="row">
                        <div class="col-12">
                            <div class="section-title-wrap text-center">
                                <div class="section-title">
                                    <h2 class="title">@lang('contact.topic')</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 justify-content-center">
                        <div class="col-lg-6">
                            <x-form.field name="name" :label="__('contact.name')" required>
                                <input type="text" name="name" id="name" required
                                       autocomplete="name"
                                       value="{{ old('name') }}"
                                       @class(['comment-form-input', 'is-invalid' => $errors->has('name')])>
                                @error('name') <p class="invalid-feedback d-block">{{ $message }}</p> @enderror
                            </x-form.field>
                        </div>

                        <div class="col-lg-6">
                            <x-form.field name="email" :label="__('contact.email')" required>
                                <input type="email" name="email" id="email" required
                                       autocomplete="email"
                                       dir="ltr"
                                       value="{{ old('email') }}"
                                       @class(['comment-form-input', 'text-start', 'is-invalid' => $errors->has('email')])>
                                @error('email') <p class="invalid-feedback d-block">{{ $message }}</p> @enderror
                            </x-form.field>
                        </div>

                        <div class="col-lg-6">
                            <x-form.field name="phone" :label="__('contact.phone')">
                                {{-- dir="ltr" so a number typed into the Arabic form is not
                                     bidi-reordered. Validation keeps it optional and free of
                                     format assumptions: it is a courtesy field for a callback,
                                     not the verified identity the OTP flow uses. --}}
                                <input type="tel" name="phone" id="phone"
                                       autocomplete="tel"
                                       dir="ltr"
                                       value="{{ old('phone') }}"
                                       class="comment-form-input text-start">
                            </x-form.field>
                        </div>

                        <div class="col-lg-6">
                            <x-form.field name="organisation" :label="__('contact.organisation')">
                                <input type="text" name="organisation" id="organisation"
                                       autocomplete="organization"
                                       value="{{ old('organisation') }}"
                                       class="comment-form-input">
                            </x-form.field>
                        </div>

                        <div class="col-lg-6">
                            <x-form.field name="subject_type" :label="__('contact.topic')"
                                          :help="__('contact.topic_hint')" required>
                                <select name="subject_type" id="subject_type" required
                                        @class(['comment-form-input', 'is-invalid' => $errors->has('subject_type')])>
                                    <option value="" disabled @selected(old('subject_type') === null)>
                                        @lang('contact.choose_topic')
                                    </option>
                                    @foreach ($topics as $topic)
                                        <option value="{{ $topic['value'] }}"
                                            @selected(old('subject_type') === $topic['value'])>
                                            {{ $topic['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('subject_type') <p class="invalid-feedback d-block">{{ $message }}</p> @enderror
                            </x-form.field>
                        </div>

                        <div class="col-lg-6">
                            <x-form.field name="subject" :label="__('contact.subject')">
                                <input type="text" name="subject" id="subject"
                                       value="{{ old('subject') }}"
                                       class="comment-form-input">
                            </x-form.field>
                        </div>

                        <div class="col-12">
                            <x-form.field name="message" :label="__('contact.message')" required>
                                {{-- `maxlength` matches the server-side `max:5000` so the
                                     visitor is told before submitting rather than after. --}}
                                <textarea name="message" id="message" rows="6" required
                                          maxlength="5000"
                                          @class(['comment-form-input', 'is-invalid' => $errors->has('message')])>{{ old('message') }}</textarea>
                                @error('message') <p class="invalid-feedback d-block">{{ $message }}</p> @enderror
                            </x-form.field>
                        </div>

                        <div class="col-lg-3 d-flex justify-content-center">
                            <button type="submit" class="btn-cirle">@lang('contact.submit')</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
