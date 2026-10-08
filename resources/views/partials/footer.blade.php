{{--
    The site footer — four columns on the brand's mauve gradient.

    Fixed from the previous version: the contact <ul> was never closed, the
    </footer> sat in the middle of column 3, and the map link + closing tags
    ended up after it. That broken markup is why the page rendered the footer
    as one unstyled stack. Everything below is now properly nested.
--}}
<footer class="d-footer" role="contentinfo">
    <div class="container">

        <div class="d-footer__grid">

            {{-- Column 1 — brand ------------------------------------------ --}}
            <div class="d-footer__brand">
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
                           rel="noopener noreferrer" target="_blank" aria-label="LinkedIn">
                            <i class="fab fa-linkedin-in" aria-hidden="true"></i>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.facebook.com/profile.php?id=100066862488270"
                           rel="noopener noreferrer" target="_blank" aria-label="Facebook">
                            <i class="fab fa-facebook-f" aria-hidden="true"></i>
                        </a>
                    </li>
                    <!-- <li>
                        <a href="https://work.me/g/5QPnbFtJq/tNBpmrtU"
                           rel="noopener noreferrer" target="_blank" aria-label="Workplace">
                            <img src="{{ asset('assets/images/workplace-icon.png') }}"
                                 alt="" aria-hidden="true"
                                 width="24" height="24"
                                 loading="lazy" decoding="async">
                        </a>
                    </li> -->
                </ul>
            </div>

            {{-- Column 2 — quick links ------------------------------------ --}}
            <nav aria-labelledby="footer-links-title">
                <h2 id="footer-links-title" class="d-footer__title">@lang('footer.quick_links')</h2>

                <ul class="d-footer__list">
                    @foreach ([
                        ['home', __('nav.home')],
                        ['programme', __('nav.programme')],
                        ['pricing', __('nav.pricing')],
                        ['sponsors', __('nav.sponsors')],

                    ] as [$route, $label])
                        @continue(! Route::has($route))
                        <li><a href="{{ route($route) }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </nav>

            {{-- Column 3 — contact ---------------------------------------- --}}
            <div>
                <h2 class="d-footer__title">@lang('nav.contact')</h2>

                {{-- dir="ltr" on machine-formatted values so the bidi algorithm
                     does not scramble phones/addresses inside Arabic text. --}}
                <address class="d-footer__address">
                    @if ($currentEdition?->venue_name)
                        <strong>{{ $currentEdition->venue_name }}</strong>
                    @endif
                    @if ($currentEdition?->venue_address)
                        <span dir="ltr">{{ $currentEdition->venue_address }}</span>
                    @endif
                    @if ($currentEdition?->city)
                        <span dir="ltr">{{ $currentEdition->city }}</span>
                    @endif
                </address>

                <ul class="d-footer__list d-footer__list--icons">
                    @if ($currentEdition?->contact_phone)
                        <li>
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $currentEdition->contact_phone) }}" dir="ltr">
                                <i class="fas fa-phone" aria-hidden="true"></i>
                                <span>{{ $currentEdition->contact_phone }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($currentEdition?->contact_email)
                        <li>
                            <a href="mailto:{{ $currentEdition->contact_email }}" dir="ltr">
                                <i class="fas fa-envelope" aria-hidden="true"></i>
                                <span>{{ $currentEdition->contact_email }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($currentEdition?->mapUrl())
                        <li>
                            <a href="{{ $currentEdition->mapUrl() }}"
                               rel="noopener noreferrer" target="_blank">
                                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                <span>@lang('contact.open_map')</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>

            {{-- Column 4 — newsletter ------------------------------------- --}}
            <div>
                <h2 class="d-footer__title">@lang('footer.newsletter')</h2>

                <p class="d-footer__desc">@lang('footer.newsletter_lede')</p>

                <form method="POST" action="{{ route('contact.store') }}" class="d-news">
                    @csrf

                    <label class="visually-hidden" for="footer-newsletter">
                        @lang('footer.newsletter_email')
                    </label>

                    {{-- Honeypot --}}
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
                        <button type="submit" class="d-news__btn">
                            <span class="visually-hidden">@lang('footer.subscribe')</span>
                            <i class="fas fa-paper-plane" aria-hidden="true"></i>
                        </button>
                    </div>

                    <p class="d-news__note">@lang('footer.newsletter_note')</p>
                </form>
            </div>

        </div>

        <div class="d-footer__bottom">
            <p>
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
<style>

    /* ==========================================================================
   ARABCIA footer
   Fonts : Archivo Bold (titles) · Aptos (content)
   Colors: gradient #2c1181 → #201062 · mauve #2a1f6e · blue #7384ff · orange #e39374
   ========================================================================== */

/* If Archivo is not already loaded site-wide, keep this line (or add it to <head>) */
@import url("https://fonts.googleapis.com/css2?family=Archivo:wght@700&display=swap");

.d-footer {
    --f-grad-a: #2c1181;
    --f-grad-b: #201062;
    --f-mauve: #2a1f6e;
    --f-blue: #7384ff;
    --f-orange: #e39374;
    --f-text: rgba(255, 255, 255, .78);
    --f-line: rgba(255, 255, 255, .14);

    position: relative;
    margin-top: 0;
    padding-block: 80px 0;
    color: var(--f-text);
    font-family: "Aptos", "Segoe UI", system-ui, -apple-system, sans-serif;
    font-size: .95rem;
    line-height: 1.7;
    background: linear-gradient(160deg, var(--f-grad-a) 0%, var(--f-grad-b) 100%);
    overflow: hidden;
}

/* thin orange rule on top — the one accent that ties it to the buttons above */
.d-footer::before {
    content: "";
    position: absolute;
    inset-inline: 0;
    top: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--f-orange), var(--f-blue));
}

.d-footer a {
    color: inherit;
    text-decoration: none;
    transition: color .2s ease;
}

.d-footer a:hover,
.d-footer a:focus-visible {
    color: var(--f-blue);
}

.d-footer a:focus-visible,
.d-news__btn:focus-visible,
.d-news input:focus-visible {
    outline: 2px solid var(--f-orange);
    outline-offset: 3px;
}

.d-footer ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

/* ---------- Grid ---------- */
.d-footer__grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1.3fr 1.5fr;
    gap: 56px 48px;
    padding-bottom: 56px;
}

/* ---------- Titles ---------- */
.d-footer__title {
    margin: 0 0 22px;
    font-family: "Archivo", "Aptos", sans-serif;
    font-weight: 700;
    font-size: 1.15rem;
    letter-spacing: .02em;
    color: #fff;
}

/* ---------- Brand ---------- */
.d-footer__logo img {
    display: block;
    height: auto;
    max-width: 160px;
    margin-bottom: 20px;
}

.d-footer__desc {
    margin: 0 0 22px;
    max-width: 46ch;
    color: var(--f-text);
}

/* the brand column shows the long intro: keep it tidy */
.d-footer__brand .d-footer__desc {
    display: -webkit-box;
    -webkit-line-clamp: 6;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* ---------- Social ---------- */
.d-social {
    display: flex;
    gap: 10px;
}

.d-social a {
    display: grid;
    place-items: center;
    width: 40px;
    height: 40px;
    border: 1px solid var(--f-line);
    border-radius: 50%;
    color: #fff;
    background: rgba(255, 255, 255, .05);
    transition: background .2s ease, border-color .2s ease, transform .2s ease;
}

.d-social a:hover,
.d-social a:focus-visible {
    color: #fff;
    background: var(--f-blue);
    border-color: var(--f-blue);
    transform: translateY(-2px);
}

.d-social img {
    max-height: 18px;
    width: auto;
}

/* ---------- Lists ---------- */
.d-footer__list li + li {
    margin-top: 10px;
}

.d-footer__list a {
    display: inline-block;
}

.d-footer__list:not(.d-footer__list--icons) a:hover {
    transform: translateX(4px);
}

[dir="rtl"] .d-footer__list:not(.d-footer__list--icons) a:hover {
    transform: translateX(-4px);
}

/* ---------- Contact ---------- */
.d-footer__address {
    margin: 0 0 18px;
    font-style: normal;
}

.d-footer__address strong {
    display: block;
    color: #fff;
    font-weight: 600;
}

.d-footer__address span {
    display: block;
}

.d-footer__list--icons a {
    display: inline-flex;
    align-items: flex-start;
    gap: 12px;
}

.d-footer__list--icons i {
    flex: 0 0 18px;
    margin-top: .35em;
    color: var(--f-orange);
    text-align: center;
}

.d-footer__list--icons a:hover i {
    color: var(--f-blue);
}

/* ---------- Newsletter ---------- */
.d-news__row {
    display: flex;
    align-items: stretch;
    gap: 8px;
}

.d-news input[type="email"] {
    flex: 1;
    min-width: 0;
    height: 48px;
    padding: 0 16px;
    font: inherit;
    color: #fff;
    background: rgba(255, 255, 255, .08);
    border: 1px solid var(--f-line);
    border-radius: 10px;
    transition: border-color .2s ease, background .2s ease;
}

.d-news input[type="email"]::placeholder {
    color: rgba(255, 255, 255, .5);
}

.d-news input[type="email"]:focus {
    outline: none;
    border-color: var(--f-orange);
    background: rgba(255, 255, 255, .12);
}

.d-news__btn {
    flex: 0 0 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border: 0;
    border-radius: 10px;
    color: var(--f-mauve);
    background: var(--f-orange);
    cursor: pointer;
    transition: background .2s ease, transform .2s ease;
}

.d-news__btn:hover {
    background: #eda78c;
    transform: translateY(-2px);
}

.d-news__note {
    margin: 12px 0 0;
    font-size: .82rem;
    color: rgba(255, 255, 255, .55);
}

/* ---------- Bottom bar ---------- */
.d-footer__bottom {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px 32px;
    padding-block: 24px;
    border-top: 1px solid var(--f-line);
    font-size: .85rem;
    color: rgba(255, 255, 255, .6);
}

.d-footer__bottom p {
    margin: 0;
}

.d-footer__legal {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 24px;
}

/* ---------- Responsive ---------- */
@media (max-width: 1100px) {
    .d-footer__grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 640px) {
    .d-footer {
        padding-top: 56px;
    }

    .d-footer__grid {
        grid-template-columns: 1fr;
        gap: 40px;
    }

    .d-footer__bottom {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (prefers-reduced-motion: reduce) {
    .d-footer *,
    .d-footer *::before {
        transition: none !important;
        transform: none !important;
    }
}
</style>