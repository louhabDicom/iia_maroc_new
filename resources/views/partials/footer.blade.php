{{--
    Site footer, ported from includes/footer.php.

    The 2024 version had an empty .footer-newsletter row whose only job was to be
    a click target for the registration page, plus a hardcoded "Conception et
    développement Di com" credit. The click target is now a real link with a real
    label, because a div with a click handler and no accessible name is
    invisible to a keyboard or a screen reader, and the credit is dropped: it
    names a contractor for work that is no longer theirs to claim.

    Everything else — the background, the social row, the navigation, the
    copyright bar — keeps the template's class names so the stylesheet applies
    unchanged.
--}}
<div class="footer-section" style="background-image: url({{ asset('assets/images/bg/footer_bg1.jpg') }});">

    {{-- Registration call to action. Rendered only while registration is open:
         a prominent invitation to a closed edition is what generates the
         "how do I still register?" email. --}}
    @if ($currentEdition?->registration_open)
        <div class="container">
            <div class="footer-newsletter" id="participe">
                <div class="row">
                    <div class="col-12 text-center">
                        <a href="{{ auth()->check() ? route('pricing') : route('register') }}"
                           class="btn-join">@lang('nav.join')</a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="footer-widget-social">
        <div class="container">
            <div class="row text-center">
                <div class="col-12">
                    <div class="social-title">
                        <h4 class="title">@lang('footer.follow_us')</h4>
                    </div>
                </div>
                <div class="col-12">
                    <ul class="social-list">
                        <li>
                            <a href="https://www.linkedin.com/company/iia-maroc-amaci/?viewAsMember=true"
                               rel="noopener noreferrer" target="_blank">
                                <i class="fab fa-linkedin-in" aria-hidden="true"></i>
                                <span class="sr-only">LinkedIn</span>
                            </a>
                        </li>
                        <li>
                            <a href="https://www.facebook.com/profile.php?id=100066862488270"
                               rel="noopener noreferrer" target="_blank">
                                <i class="fab fa-facebook-f" aria-hidden="true"></i>
                                <span class="sr-only">Facebook</span>
                            </a>
                        </li>
                        <li>
                            <a href="https://work.me/g/5QPnbFtJq/tNBpmrtU"
                               rel="noopener noreferrer" target="_blank">
                                <img src="{{ asset('assets/images/workplace-icon.png') }}"
                                     style="max-height: 28px;" alt="Workplace">
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-widget-navigation text-center">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="footer-navigation">
                        <ul>
                            <li><a href="{{ route('home') }}">@lang('nav.home')</a></li>
                            <li><a href="{{ route('programme') }}">@lang('nav.programme')</a></li>
                            <li><a href="{{ route('speakers') }}">@lang('nav.speakers')</a></li>
                            <li><a href="{{ route('pricing') }}">@lang('nav.pricing')</a></li>
                            <li><a href="{{ route('venue') }}">@lang('nav.venue')</a></li>
                            <li><a href="{{ route('sponsors') }}">@lang('nav.sponsors')</a></li>
                            <li><a href="{{ route('contact') }}">@lang('nav.contact')</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-copyright-area">
        <div class="container">
            <div class="footer-copyright-wrap">
                <div class="row align-items-center">
                    <div class="col-lg-12">
                        <div class="copyright-text text-center">
                            <p>
                                &copy; {{ $currentEdition?->year ?? date('Y') }}
                                {{ $currentEdition?->organiser ?? __('site.organiser') }}
                                &middot; @lang('footer.rights')
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
