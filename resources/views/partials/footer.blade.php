{{--
    The site footer.

    Rebuilt as four real columns. The 2024 footer was a centred logo, three
    social icons, a single row of links and a copyright line — which on a wide
    screen left two large empty dark areas either side of a narrow stack, and
    carried no telephone number, no address and no mailbox anywhere. A visitor
    who had scrolled past the contact page had to scroll back to find any of
    them.

    So: brand and blurb, quick links, the organisers' real contact details, and
    a newsletter sign-up. Everything on it is a row or a config value rather
    than a string typed into the template, so the next edition changes the
    footer without a designer.
--}}
<footer class="d-footer" role="contentinfo">
    <div class="container">

        <div class="d-footer__grid">

            {{-- Column 1 — the brand -------------------------------------- --}}
            <div>
                <span class="d-footer__logo">
                    <img src="{{ \App\Support\Brand::logoUrl() }}"
                         width="{{ \App\Support\Brand::logo()['width'] }}"
                         height="{{ \App\Support\Brand::logo()['height'] }}"
                         alt="{{ __('site.site_name') }}"
                         loading="lazy"
                         decoding="async">
                </span>

                <p class="d-footer__desc">
                    {{ $currentEdition?->introduction ?? __('site.organiser') }}
                </p>

                <ul class="d-social">
                    <li>
                        <a href="https://www.linkedin.com/company/iia-maroc-amaci/?viewAsMember=true"
                           rel="noopener noreferrer" target="_blank">
                            <i class="fab fa-linkedin-in" aria-hidden="true"></i>
                            <span class="visually-hidden">LinkedIn</span>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.facebook.com/profile.php?id=100066862488270"
                           rel="noopener noreferrer" target="_blank">
                            <i class="fab fa-facebook-f" aria-hidden="true"></i>
                            <span class="visually-hidden">Facebook</span>
                        </a>
                    </li>
                    <li>
                        <a href="https://work.me/g/5QPnbFtJq/tNBpmrtU"
                           rel="noopener noreferrer" target="_blank">
                            {{-- Intrinsic size declared so the icon does not shift
                                 the row as it loads. The image is a small white
                                 mark; the numbers are its real proportions. --}}
                            <img src="{{ asset('assets/images/workplace-icon.png') }}"
                                 alt="" aria-hidden="true"
                                 width="24" height="24"
                                 style="max-height:20px;"
                                 loading="lazy"
                                 decoding="async">
                            <span class="visually-hidden">Workplace</span>
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Column 2 — quick links ------------------------------------- --}}
            <nav aria-labelledby="footer-links-title">
                <h2 id="footer-links-title" class="d-footer__title">@lang('footer.quick_links')</h2>

                <ul class="d-footer__list">
                    @foreach ([
                        ['home', __('nav.home')],
                        ['programme', __('nav.programme')],
                        ['speakers', __('nav.speakers')],
                        ['pricing', __('nav.pricing')],
                        ['venue', __('nav.venue')],
                        ['sponsors', __('nav.sponsors')],
                        ['archive', __('nav.archive')],
                    ] as [$route, $label])
                        @continue(! Route::has($route))
                        <li><a href="{{ route($route) }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </nav>


            {{-- Column 3 — real contact details ---------------------------- --}}
            <div>
                <h2 class="d-footer__title">@lang('nav.contact')</h2>

                {{-- `dir="ltr"` on the machine-formatted values: an address, a
                     mailbox and a telephone number are all reordered by the bidi
                     algorithm when they sit inside Arabic text without it, which
                     is how a phone number ends up printed as "113 401 678 212+". --}}
                <address class="d-footer__address">
                    @if ($currentEdition?->venue_name)
                        <strong style="color:#fff;">{{ $currentEdition->venue_name }}</strong><br>
                    @endif
                    @if ($currentEdition?->venue_address)
                        <span dir="ltr">{{ $currentEdition->venue_address }}</span><br>
                    @endif
                    @if ($currentEdition?->city)
                        <span dir="ltr">{{ $currentEdition->city }}</span>
                    @endif
                </address>

                <ul class="d-footer__list">
                    @if ($currentEdition?->contact_phone)
                        <li>
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $currentEdition->contact_phone) }}" dir="ltr">
                                <i class="fas fa-phone me-2" aria-hidden="true"></i>
                                {{ $currentEdition->contact_phone }}
                            </a>
                        </li>
                    @endif

                    @if ($currentEdition?->contact_email)
                        <li>
                            <a href="mailto:{{ $currentEdition->contact_email }}" dir="ltr">
                                <i class="fas fa-envelope me-2" aria-hidden="true"></i>
                                {{ $currentEdition->contact_email }}
                            </a>
                        </li>

            {{-- Column 4 — newsletter --------------------------------------- --}}
            <div>
                <h2 class="d-footer__title">@lang('footer.newsletter')</h2>

                <p class="d-footer__desc">@lang('footer.newsletter_lede')</p>

                {{-- A real form with a real POST target and a real label.

                     There is no newsletter route in this application yet, so the
                     form posts to the contact endpoint with the subject already
                     chosen — which makes it work today instead of being a field
                     that silently discards what is typed into it. Pointing it at
                     a dedicated route is a one-line change once that exists. --}}
                <form method="POST" action="{{ route('contact.store') }}" class="d-news">
                    @csrf

                    <label class="visually-hidden" for="footer-newsletter">
                        @lang('footer.newsletter_email')
                    </label>

                    {{-- A honeypot. A real person never sees it; an automated
                         poster fills in every field it finds. --}}
                    <div class="visually-hidden" aria-hidden="true">
                        <label for="footer-newsletter-company">@lang('misc.leave_blank')</label>
                        <input type="text" id="footer-newsletter-company" name="company"
                               tabindex="-1" autocomplete="off">
                    </div>

                    <input type="hidden" name="subject_type" value="other">
                    <input type="hidden" name="subject" value="Newsletter subscription">

                    <div class="d-news__row">
                        <input type="email"
                               id="footer-newsletter"
                               name="email"
                               required
                               autocomplete="email"
                               dir="ltr"
                               placeholder="{{ __('footer.newsletter_email') }}">
                        <button type="submit" class="d-btn d-btn--accent d-btn--sm">
                            <span class="visually-hidden">@lang('footer.subscribe')</span>
                            <i class="fas fa-paper-plane" aria-hidden="true"></i>
                        </button>
                    </div>

                    <p class="d-news__error">@lang('footer.newsletter_note')</p>
                </form>
            </div>

        </div>

        <div class="d-footer__bottom">
            <p class="mb-0">
                &copy; {{ $currentEdition?->year ?? date('Y') }}
                {{ $currentEdition?->organiser ?? __('site.organiser') }}
                &middot; @lang('footer.rights')
            </p>

            <ul class="d-footer__legal">
                <li><a href="{{ route('contact') }}">@lang('footer.privacy')</a></li>
                <li><a href="{{ route('contact') }}">@lang('footer.legal')</a></li>
                <li><a href="{{ route('contact') }}">@lang('footer.accessibility')</a></li>
            </ul>
        </div>

    </div>
</footer>

                    @endif

                    @if ($currentEdition?->mapUrl())
                        <li>
                            <a href="{{ $currentEdition->mapUrl() }}"
                               rel="noopener noreferrer" target="_blank">
                                <i class="fas fa-location-dot me-2" aria-hidden="true"></i>
                                @lang('contact.open_map')
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
