@extends('layouts.app')

@section('title', __('pricing.title'))
@section('description', __('pricing.currency_note'))

@section('content')

    {{-- Currency is settled here, in the hero band, rather than in a footnote
         under the cards. A tariff the visitor cannot interpret is a tariff they
         will not act on, and the note is short enough to belong in the lede. --}}
    <x-front.hero
        :title="__('pricing.hero.title', ['year' => $edition->year])"
        :eyebrow="$edition->identityLabel()"
        :lede="__('pricing.hero.lede')"
        :crumbs="[__('nav.pricing') => null]"
        :facts="[
            ['icon' => 'fa-calendar-alt', 'label' => $edition->dateLine($locale)],
            ['icon' => 'fa-map-marker-alt',  'label' => $edition->venueLine($locale)],
        ]"
        image="assets/images/bg/price_bg.jpg" />

    {{-- What a place buys, stated once in three lines before the tariffs. The
         individual inclusions live on each card; this is the summary above the
         fold so the visitor knows the deal before comparing figures. --}}
    <section class="section-padding-04 pt-0" aria-labelledby="pricing-what-title">
        <div class="container">
            <div class="pricing-includes ux-reveal">
                <x-design.section-head id="pricing-what-title" :title="__('pricing.what.title')" />

                <ul class="ux-checks ux-checks--row text-start">
                    @foreach (__('pricing.what.items') as $item)
                        <li class="ux-checks__item">{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <section class="ux-section section-padding-03 ux-section--defer"
             aria-labelledby="pricing-heading">
        <div class="container">

            <h2 id="pricing-heading" class="visually-hidden">@lang('pricing.title')</h2>

            {{-- The states that gate ordering, stated above the cards rather than
                 discovered at the checkout. These are notices rather than alerts:
                 none of them is an error, and an error box would say otherwise. --}}
            <div class="d-flex flex-column gap-3 mb-5">
                @unless ($registrationOpen)
                    <div class="ux-notice ux-notice--warning ux-reveal" role="status">
                        <i class="fas fa-lock ux-notice__icon" aria-hidden="true"></i>
                        <span>@lang('pricing.closed')</span>
                    </div>
                @endunless

                @auth
                    @unless ($canOrder)
                        <div class="ux-notice ux-notice--warning ux-reveal" role="status">
                            <i class="fas fa-exclamation-circle ux-notice__icon" aria-hidden="true"></i>

                            {{-- Told what to do about it here, with a link, rather than
                                 left to be discovered when the form refuses. --}}
                            <span>
                                @if (auth()->user()->hasConfirmedTotp())
                                    @lang('register.terms_required')
                                @else
                                    <a href="{{ route('totp.setup') }}" class="ux-btn ux-btn--ghost">
                                        @lang('totp.title')
                                    </a>
                                @endif
                            </span>
                        </div>
                    @endunless
                @endauth
            </div>

            @if ($ticketTypes->isEmpty())
                <x-empty-state :message="__('state.empty')" icon="fas fa-tags" />
            @else
                <div class="row g-4 justify-content-center" data-ux-stagger="90">
                    @foreach ($ticketTypes as $index => $type)
                        @php
                            // Which price applies is decided by the server from the
                            // Membership record, never from anything the visitor chose.
                            // Showing the member rate to an active member is a
                            // convenience; it grants nothing.
                            $amount = $type->priceFor($isMember);
                            $includes = (array) ($type->includes ?: []);

                            // Only flag a recommended tier once there is a genuine
                            // choice. With one or two types, flagging the first would
                            // be arbitrary dressing.
                            $featured = $ticketTypes->count() >= 3 && $index === 0;
                        @endphp

                        <div class="col-lg-6 col-12">
                            <article @class(['ux-price', 'ux-price--featured' => $featured])>

                                @if ($featured)
                                    <span class="ux-price__flag">@lang('pricing.recommended')</span>
                                @endif

                                <h3 class="ux-price__name">{{ $type->name }}</h3>

                                <p class="ux-price__audience">
                                    @lang($isMember ? 'pricing.member' : 'pricing.standard')
                                </p>

                                {{-- dir="ltr" so the amount is not bidi-reordered next to
                                     a currency symbol on the Arabic page. --}}
                                <span class="ux-price__amount" dir="ltr">
                                    {{ $type->formatAmount($amount) }}
                                </span>

                                <p class="ux-price__note">@lang('pricing.per_person')</p>

                                @if ($type->description)
                                    <p class="text-muted mb-3">{{ $type->description }}</p>
                                @endif

                                @if ($includes !== [])
                                    <p class="fw-bold mt-3 mb-2">@lang('pricing.includes')</p>

                                    <ul class="ux-checks text-start">
                                        @foreach ($includes as $item)
                                            <li class="ux-checks__item">{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                <div class="ux-price__foot">
                                    @if (! $registrationOpen)
                                        {{-- A real disabled button rather than a styled
                                             <span>: it looks identical, and a screen
                                             reader still reports it as unavailable. --}}
                                        <button type="button" class="ux-btn ux-btn--ghost w-100" disabled>
                                            <span>@lang('pricing.closed')</span>
                                        </button>
                                    @elseif (! auth()->check())
                                        {{-- Signing in is the only thing standing between
                                             this visitor and the form, so the card says
                                             that instead of presenting a button that
                                             would fail when pressed. --}}
                                        <a href="{{ route('login') }}"
                                           class="ux-btn ux-btn--primary w-100"
                                           data-ux-magnetic="0.14">
                                            <span>@lang('login.title')</span>
                                        </a>
                                    @elseif (! $canOrder)
                                        {{-- Signed in but blocked, with the reason already
                                             stated in the notice above. This points at
                                             the step that unblocks it.

                                             Same two states the notice above tests, in
                                             the same order: `canRegister()` is false
                                             either because the authenticator is not
                                             set up or because the terms were never
                                             accepted, and there is no third. --}}
                                        <a href="{{ auth()->user()->hasConfirmedTotp() ? route('account') : route('totp.setup') }}"
                                           class="ux-btn ux-btn--primary w-100"
                                           data-ux-magnetic="0.14">
                                            <span>
                                                @lang(auth()->user()->hasConfirmedTotp() ? 'register.terms_required' : 'totp.title')
                                            </span>
                                        </a>
                                    @else
                                        {{-- A real quantity form, not a link to a
                                             registration form that asks for the same
                                             numbers a second time.

                                             The member count defaults to zero: a single
                                             place is charged at the standard rate until
                                             the server confirms a membership, so the
                                             form never promises a discount it might
                                             not grant. --}}
                                        <form method="POST" action="{{ route('cart.store') }}">
                                            @csrf
                                            <input type="hidden" name="ticket_type_id" value="{{ $type->id }}">

                                            <div class="d-flex gap-2 justify-content-center mb-2">
                                                <div>
                                                    <label class="ux-price__note d-block text-center"
                                                           for="qty-{{ $type->id }}">
                                                        @lang('order.quantity')
                                                    </label>
                                                    <input type="number"
                                                           id="qty-{{ $type->id }}"
                                                           name="quantity"
                                                           value="1" min="1" max="{{ $type->max_quantity ?: 20 }}"
                                                           class="form-control text-center"
                                                           style="width: 5rem;">
                                                </div>
                                                <div>
                                                    <label class="ux-price__note d-block text-center"
                                                           for="mem-{{ $type->id }}">
                                                        @lang('order.member_places')
                                                    </label>
                                                    <input type="number"
                                                           id="mem-{{ $type->id }}"
                                                           name="member_quantity"
                                                           value="0" min="0" max="{{ $type->max_quantity ?: 20 }}"
                                                           class="form-control text-center"
                                                           style="width: 5rem;">
                                                </div>
                                            </div>

                                            @error('quantity')
                                                <p class="ux-notice ux-notice--danger" role="alert">{{ $message }}</p>
                                            @enderror
                                            @error('member_quantity')
                                                <p class="ux-notice ux-notice--danger" role="alert">{{ $message }}</p>
                                            @enderror
                                            @error('ticket_type_id')
                                                <p class="ux-notice ux-notice--danger" role="alert">{{ $message }}</p>
                                            @enderror

                                            <button type="submit"
                                                    class="ux-btn ux-btn--primary w-100"
                                                    data-ux-magnetic="0.14">
                                                <span>@lang('order.cart.add')</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            @endif

            <p class="pricing-note ux-reveal mt-4">@lang('pricing.note')</p>

            {{-- Recruitment band, between the tariffs and the two account paths.
                 A light-blue horizontal strip: the visitor who is not yet a
                 member is the one for whom the member rate is out of reach, so
                 this is the one place on the page that sells membership. --}}
            <aside class="pricing-member-band ux-reveal" role="note">
                <span class="pricing-member-band__icon" aria-hidden="true">
                    <i class="fas fa-id-badge"></i>
                </span>

                <div class="pricing-member-band__body">
                    <h2 class="pricing-member-band__title">@lang('pricing.membership.title')</h2>
                    <p class="pricing-member-band__text">@lang('pricing.membership.text')</p>
                </div>

                <a href="{{ route('contact') }}" class="ux-btn ux-btn--primary pricing-member-band__cta">
                    <span>@lang('pricing.membership.cta')</span>
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </aside>

        </div>
    </section>

    {{-- The fork: sign in, or create an account. Two equal columns so neither
         reads as the default, because neither is. --}}
    <section class="section-padding-03 ux-section--defer" aria-labelledby="pricing-paths-title">
        <div class="container">
            <x-design.section-head
                id="pricing-paths-title"
                :title="__('pricing.paths.title')"
                :lede="__('pricing.paths.lede')"
                align="center" />

            <div class="pricing-paths">

                <article class="pricing-path ux-reveal">
                    <h3 class="pricing-path__title">@lang('pricing.paths.existing_title')</h3>
                    <p class="pricing-path__text">@lang('pricing.paths.existing_text')</p>

                    <form method="POST" action="{{ route('login.store') }}" class="pricing-path__form">
                        @csrf

                        <x-form.field name="identifier" :label="__('login.identifier')" required>
                            <input type="text" name="identifier" id="pricing-identifier" required
                                   autocomplete="username"
                                   dir="ltr"
                                   value="{{ old('identifier') }}"
                                   @class(['input', 'input-error' => $errors->has('identifier')])
                                   @if ($errors->has('identifier')) aria-invalid="true" aria-describedby="identifier-error" @endif>
                        </x-form.field>

                        <x-form.field name="password" :label="__('register.password')" required>
                            <input type="password" name="password" id="pricing-password" required
                                   autocomplete="current-password"
                                   dir="ltr"
                                   @class(['input', 'input-error' => $errors->has('password')])
                                   @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
                        </x-form.field>

                        <div class="app-check mb-3">
                            <input type="checkbox" name="remember" id="pricing-remember" value="1" @checked(old('remember'))>
                            <label for="pricing-remember">@lang('login.remember')</label>
                        </div>

                        <button type="submit" class="ux-btn ux-btn--primary w-100" data-ux-magnetic="0.14">
                            <span>@lang('pricing.paths.existing_submit')</span>
                        </button>
                    </form>
                </article>

                <article class="pricing-path pricing-path--create ux-reveal">
                    <h3 class="pricing-path__title">@lang('pricing.paths.create_title')</h3>
                    <p class="pricing-path__text">@lang('pricing.paths.create_text')</p>

                    <a href="{{ route('register') }}"
                       class="ux-btn ux-btn--primary w-100 pricing-path__cta"
                       data-ux-magnetic="0.14">
                        <span>@lang('pricing.paths.create_cta')</span>
                    </a>
                </article>

            </div>
        </div>
    </section>

@endsection

<style>
    /* ---------------------------------------------------------------------
       Registration page — the "what's included" strip, the recruitment band
       and the two account paths. Classes prefixed `pricing-` so nothing here
       can reach another page.
       --------------------------------------------------------------------- */
    .pricing-includes {
        padding: 34px clamp(20px, 4vw, 44px);
        border-radius: 20px;
        background: #f6f4ff;
        border: 1px solid #e4dffb;
        text-align: center;
    }

    .pricing-includes .d-head { align-items: center; }
    .pricing-includes .d-head__lede { margin-inline: auto; }

    .ux-checks--row {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 14px 40px;
        margin-block-start: 18px;
        text-align: start;
    }

    .pricing-note {
        max-width: 60ch;
        margin-inline: auto;
        text-align: center;
        font-size: .85rem;
        line-height: 1.6;
        color: var(--ux-ink-soft, #5f6384);
    }

    /* ---------- Membership recruitment band ---------- */
    .pricing-member-band {
        display: flex;
        align-items: center;
        gap: 24px;
        margin-block-start: 44px;
        padding: 28px 34px;
        border-radius: 20px;
        background: linear-gradient(120deg, #eaf2ff 0%, #e3ecff 100%);
        border: 1px solid #cddcff;
    }

    .pricing-member-band__icon {
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        width: 56px;
        height: 56px;
        border-radius: 16px;
        background: #fff;
        color: #2b1d9a;
        font-size: 1.4rem;
    }

    .pricing-member-band__body { flex: 1 1 auto; }

    .pricing-member-band__title {
        margin: 0 0 6px;
        font-size: 1.1rem;
        font-weight: 700;
        color: #1b1464;
    }

    .pricing-member-band__text {
        margin: 0;
        max-width: 62ch;
        font-size: .9rem;
        line-height: 1.6;
        color: #4a4d6b;
    }

    .pricing-member-band__cta {
        flex: 0 0 auto;
        white-space: nowrap;
    }

    [dir="rtl"] .pricing-member-band__cta .fa-arrow-right { transform: scaleX(-1); }

    /* ---------- The two account paths ---------- */
    .pricing-paths {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 24px;
        margin-block-start: 40px;
    }

    .pricing-path {
        display: flex;
        flex-direction: column;
        padding: 34px 32px;
        border-radius: 20px;
        background: #fff;
        border: 1px solid #e4dffb;
        box-shadow: 0 20px 44px -30px rgba(60, 40, 160, .4);
    }

    .pricing-path--create {
        background: #f6f4ff;
        justify-content: center;
    }

    .pricing-path__title {
        margin: 0 0 10px;
        font-size: 1.15rem;
        font-weight: 700;
        color: #1b1464;
    }

    .pricing-path__text {
        margin: 0 0 22px;
        font-size: .88rem;
        line-height: 1.65;
        color: #4a4d6b;
    }

    .pricing-path__form { margin: 0; }
    .pricing-path__cta { margin-block-start: auto; }

    @media (max-width: 991.98px) {
        .pricing-paths { grid-template-columns: minmax(0, 1fr); }
        .pricing-member-band {
            flex-direction: column;
            align-items: flex-start;
            text-align: start;
        }
        .pricing-member-band__cta { width: 100%; justify-content: center; }
    }
</style>