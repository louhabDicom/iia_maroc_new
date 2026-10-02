@extends('layouts.app')

@section('title', __('contact.title'))
@section('description', __('contact.intro'))

@section('content')

    <x-page-hero
        :title="__('contact.title')"
        :eyebrow="$edition?->identityLabel()"
        :crumbs="[__('nav.contact') => null]"
        :facts="$edition ? [
            ['icon' => 'fa-calendar-days', 'label' => $edition->dateLine($locale)],
            ['icon' => 'fa-location-dot',  'label' => $edition->venueLine($locale)],
        ] : []"
        image="assets/images/bg/map_bg.png" />

    {{-- ---------------------------------------------------------------------
        Reach us directly.

        Four cards, because a delegate arriving in Rabat has four different
        questions and the 2024 page answered them in one undifferentiated block:
        where is it, how do I phone, who do I email, and who is running this.
        Each card carries an answer a person can act on — a `tel:` link and a
        `mailto:` link, not a string of digits to re-type.

        The cards themselves are built in the controller. Deciding that a card is
        worth printing is a data question, and answering it here is what keeps the
        template free of the `@if` tower the 2024 build needed.
    --------------------------------------------------------------------- --}}
    @if ($directCards !== [])
        <section class="ux-section section-padding-03" aria-labelledby="reach-heading">
            <div class="container">

                <x-section-head
                    id="reach-heading"
                    :eyebrow="__('contact.direct')"
                    :title="__('contact.reach_us')"
                    :lede="__('contact.reach_lede')"
                    :level="2"
                    align="center"
                    class="mb-5" />

                <div class="row g-4" data-ux-stagger="80">
                    @foreach ($directCards as $card)
                        <div class="col-lg-3 col-md-6 ux-reveal">
                            <div class="ux-card ux-card--edge ux-card--lift h-100 p-4 ux-contact-card">

                                <span class="ux-tile__icon" aria-hidden="true">
                                    <i class="fas {{ $card['icon'] }}"></i>
                                </span>

                                <h3 class="ux-contact-card__label">{{ $card['label'] }}</h3>

                                {{-- `dir="ltr"`: an address, a mailbox and a
                                     telephone number are all reordered by the bidi
                                     algorithm when they sit inside Arabic text
                                     without it. --}}
                                <p class="ux-contact-card__value" dir="ltr">
                                    @foreach ($card['lines'] as $line)
                                        <span>{{ $line }}</span>
                                    @endforeach
                                </p>

                                @if ($card['action'])
                                    {{-- Kept on one line on purpose: a multi-line
                                         `@if` inside a tag's attribute list is
                                         compiled as PHP and the whole page dies
                                         with a parse error. --}}
                                    <a href="{{ $card['action']['href'] }}" class="ux-contact-card__action" @if ($card['action']['external']) rel="noopener noreferrer" target="_blank" @endif>
                                        {{ $card['action']['label'] }}
                                        <i class="fas fa-arrow-right ux-contact-card__arrow" aria-hidden="true"></i>
                                    </a>
                                @endif

                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

    {{-- ---------------------------------------------------------------------
        The organising committee.

        The six named officers of IIA Maroc, with the direct line and mailbox the
        organisers themselves publish on their own documents. This is the part of
        the page that used to be missing entirely: the 2024 build asked a visitor
        to write to a form and then gave them nobody to write to.
    --------------------------------------------------------------------- --}}
    @if ($team->isNotEmpty())
        <section class="ux-section section-padding-03 ux-section--tint"
                 aria-labelledby="committee-heading">
            <div class="container">

                <x-section-head
                    id="committee-heading"
                    :eyebrow="__('site.host_institute')"
                    :title="__('contact.committee')"
                    :lede="__('contact.committee_lede')"
                    :level="2"
                    align="center"
                    class="mb-5" />

                <ul class="row g-4 list-unstyled" data-ux-stagger="70">
                    @foreach ($team as $member)
                        <li class="col-lg-4 col-md-6 ux-reveal">
                            <div class="ux-card ux-card--edge ux-card--lift h-100 p-4 ux-officer">

                                <div class="ux-officer__head">
                                    {{-- Initials rather than a silhouette: there is
                                         no photograph of these officers on file, and
                                         a stock avatar would put a stranger's face
                                         under a real person's name. --}}
                                    <span class="ux-officer__avatar" aria-hidden="true">
                                        {{ $member->initials() }}
                                    </span>

                                    <div class="ux-officer__id">
                                        <h3 class="ux-officer__name">{{ $member->name }}</h3>

                                        @if ($member->role)
                                            <p class="ux-officer__role">{{ $member->role }}</p>
                                        @endif

                                        @if ($member->organisation)
                                            <p class="ux-officer__org">{{ $member->organisation }}</p>
                                        @endif
                                    </div>
                                </div>

                                <ul class="ux-officer__lines list-unstyled">
                                    @if ($member->phone)
                                        <li>
                                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $member->phone) }}" dir="ltr">
                                                <i class="fas fa-phone" aria-hidden="true"></i>
                                                <span>{{ $member->phone }}</span>
                                            </a>
                                        </li>
                                    @endif

                                    @if ($member->email)
                                        <li>
                                            <a href="mailto:{{ $member->email }}" dir="ltr">
                                                <i class="fas fa-envelope" aria-hidden="true"></i>
                                                <span>{{ $member->email }}</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>

                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

        </section>
    @endif

    {{-- ---------------------------------------------------------------------
        The form. Deliberately on the same page as the people above: a visitor
        who has just read who to email and then meets a form has been told to do
        the harder thing.
    --------------------------------------------------------------------- --}}
    <div class="contact-form-section section-padding-03">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-10 text-center mb-5">
                    <span class="ux-eyebrow">@lang('contact.form_eyebrow')</span>
                    <h2 class="title mt-2">@lang('contact.form_title')</h2>
                    <p class="ux-section-lede">@lang('contact.form_lede')</p>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="contact-form-wrap ux-card ux-card--edge p-4 p-md-5">
                        <form method="POST" action="{{ route('contact.store') }}" class="contact-form">
                            @csrf

                            <div class="row g-4">
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
                                               @class(['comment-form-input', 'is-invalid' => $errors->has('email')])>
                                        @error('email') <p class="invalid-feedback d-block">{{ $message }}</p> @enderror
                                    </x-form.field>
                                </div>

                                <div class="col-lg-6">
                                    <x-form.field name="phone" :label="__('contact.phone')">
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
                                        {{-- `maxlength` matches the server-side `max:5000`, so the
                                             visitor is told before submitting rather than after. --}}
                                        <textarea name="message" id="message" rows="6" required
                                                  maxlength="5000"
                                                  @class(['comment-form-input', 'is-invalid' => $errors->has('message')])>{{ old('message') }}</textarea>
                                        @error('message') <p class="invalid-feedback d-block">{{ $message }}</p> @enderror
                                    </x-form.field>
                                </div>

                                <div class="col-12 d-flex justify-content-center">
                                    <button type="submit" class="ux-btn ux-btn--primary" data-ux-magnetic="0.18">
                                        <span>@lang('contact.submit')</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

                                               class="comment-form-input">
                                    </x-form.field>
                                </div>

