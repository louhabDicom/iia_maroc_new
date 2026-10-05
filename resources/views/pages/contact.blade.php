@extends('layouts.app')

@section('title', __('contact.title'))
@section('description', __('contact.intro'))

@section('content')

<div class="d-page d-page--contact">

    <x-front.hero
        :title="__('contact.title')"
        :eyebrow="$edition?->identityLabel()"
        :lede="__('contact.hero_lede')"
        :crumbs="[__('nav.contact') => null]"
        :facts="$edition ? [
            ['icon' => 'fa-calendar-days', 'label' => $edition->dateLine($locale)],
            ['icon' => 'fa-location-dot',  'label' => $edition->venueLine($locale)],
        ] : []"
        :cta-label="$edition?->mapUrl() ? __('contact.open_map') : null"
        :cta-url="$edition?->mapUrl()"
        :cta-url-external="(bool) $edition?->mapUrl()"
        :secondary-label="__('programme.title')"
        :secondary-url="route('programme')"
        image="assets/images/bg/form_bg.jpg" />

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
        <section class="d-section d-section--alt" aria-labelledby="reach-heading">
            <div class="container">

                <x-design.section-head
                    id="reach-heading"
                    :eyebrow="__('contact.direct')"
                    :title="__('contact.reach_us')"
                    :lede="__('contact.reach_lede')" />

                <ul class="d-cards" data-ux-stagger="80">
                    @foreach ($directCards as $card)
                        <li>
                            <div class="d-card d-card--hover d-contact-card">
                                <span class="d-feature__icon" aria-hidden="true">
                                    <i class="fas {{ $card['icon'] }}"></i>
                                </span>

                                <h2 class="d-contact-card__label">{{ $card['label'] }}</h2>

                                {{-- `dir="ltr"`: an address, a mailbox and a
                                     telephone number are all reordered by the bidi
                                     algorithm when they sit inside Arabic text
                                     without it. --}}
                                <p class="d-contact-card__value" dir="ltr">
                                    @foreach ($card['lines'] as $line)
                                        <span>{{ $line }}</span>
                                    @endforeach
                                </p>

                                @if ($card['action'])
                                    {{-- Kept on one line on purpose: a multi-line
                                         `@if` inside a tag's attribute list is
                                         compiled as PHP and the whole page dies
                                         with a parse error. --}}
                                    <a href="{{ $card['action']['href'] }}" class="d-contact-card__action" @if ($card['action']['external']) rel="noopener noreferrer" target="_blank" @endif>
                                        {{ $card['action']['label'] }}
                                        <i class="fas fa-arrow-right d-contact-card__arrow" aria-hidden="true"></i>
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ---------------------------------------------------------------------
        The organising committee.

        The named officers of IIA Maroc, with the direct line and mailbox the
        organisers themselves publish on their own documents. This is the part
        of the page that used to be missing entirely: the 2024 build asked a
        visitor to write to a form and then gave them nobody to write to.
    --------------------------------------------------------------------- --}}
    @if ($team->isNotEmpty())
        <section class="d-section" aria-labelledby="committee-heading">
            <div class="container">

                <x-design.section-head
                    id="committee-heading"
                    :eyebrow="__('site.host_institute')"
                    :title="__('contact.committee')"
                    :lede="__('contact.committee_lede')"
                    align="center" />

                {{-- A grid rather than a single column: a committee is looked up by
                     name, and a person scanning for one officer should find them
                     without reading three cards to get there. --}}
                <ul class="d-cards" data-ux-stagger="70">
                    @foreach ($team as $member)
                        <li>
                            <article class="d-card d-card--hover d-officer">
                                <div class="d-officer__head">
                                    {{-- Initials rather than a silhouette: there is
                                         no photograph of these officers on file, and
                                         a stock avatar would put a stranger's face
                                         under a real person's name. --}}
                                    <span class="d-officer__avatar" aria-hidden="true">
                                        {{ $member->initials() }}
                                    </span>

                                    <div class="d-officer__id">
                                        <h3 class="d-officer__name">{{ $member->name }}</h3>

                                        @if ($member->role)
                                            <p class="d-officer__role">{{ $member->role }}</p>
                                        @endif

                                        @if ($member->organisation)
                                            <p class="d-officer__org">{{ $member->organisation }}</p>
                                        @endif
                                    </div>
                                </div>

                                <ul class="d-officer__lines">
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
                            </article>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ---------------------------------------------------------------------
        The form. Deliberately on the same page as the people above: a visitor
        who has just read who to email and then meets a form has been told to do
        the harder thing.
    --------------------------------------------------------------------- --}}
    <section class="d-section d-section--alt" aria-labelledby="form-heading">
        <div class="container">

            <x-design.section-head
                id="form-heading"
                :eyebrow="__('contact.form_eyebrow')"
                :title="__('contact.form_title')"
                :lede="__('contact.form_lede')"
                align="center" />

            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="d-form-panel d-form">
                        <form method="POST" action="{{ route('contact.store') }}">
                            @csrf

                            <div class="row g-4">
                                <div class="col-lg-6">
                                    <x-form.field name="name" :label="__('contact.name')" required>
                                        <input type="text" name="name" id="name" required
                                               autocomplete="name"
                                               value="{{ old('name') }}"
                                               @class(['comment-form-input', 'is-invalid' => $errors->has('name')])>
                                    </x-form.field>
                                </div>

                                <div class="col-lg-6">
                                    <x-form.field name="email" :label="__('contact.email')" required>
                                        <input type="email" name="email" id="email" required
                                               autocomplete="email"
                                               dir="ltr"
                                               value="{{ old('email') }}"
                                               @class(['comment-form-input', 'is-invalid' => $errors->has('email')])>
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
                                    </x-form.field>
                                </div>

                                <div class="col-12 d-flex justify-content-center">
                                    <button type="submit" class="d-btn d-btn--primary">
                                        <span>@lang('contact.submit')</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>

@endsection
