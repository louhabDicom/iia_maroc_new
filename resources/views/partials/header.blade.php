{{--
    Site header, ported from includes/header.php and includes/header-mobile.php.

    The markup keeps the template's class names — .exvent-header-section,
    .header-wrap, .main-menu, .header-meta — because assets/css/style.css
    addresses those classes directly. Changing them to utility classes would
    mean restyling the design rather than reusing it.

    What changed against the 2024 build, and why:

      * The login form posts to the app's own route instead of an AJAX call to
        login_form.php. Two fields fit the modal; a failed post comes back as a
        redirect with errors, which is why the modal is reopened below when the
        error bag is not empty. Real server-side auth also means the session
        cookie, the CSRF token and the rate limiting all apply, none of which
        the AJAX version had.

      * Registration is a link, not a modal tab. The app asks for a first name,
        a last name, a phone and a password before it sends an OTP, and
        cramming that into a modal is how the 2024 version ended up with a form
        that validated in the browser and then lost half the fields.

      * The 2024 build gated the speaker submissions behind a hand-rolled access
        code checked by validate_access_code.php. That is a password in a query
        string with no rate limit and no revocation, so the link is simply gated
        on being signed in; the submissions page itself decides what an
        authenticated user may see.
--}}

{{-- Sign-in modal. Only rendered for guests: a signed-in visitor has no use for
     it, and shipping a login form to a logged-in page is how credential
     phishing gets a foothold. Reopened automatically when a post comes back
     with errors, which is the case where the visitor most needs to see it. --}}
@guest
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-4">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('nav.close')"></button>

                    <h3 class="text-center" id="loginModalLabel">@lang('action.login')</h3>

                    <div class="hero-form mt-0">
                        <form method="POST" action="{{ route('login.store') }}" class="exvent-form">
                            @csrf

                            <div class="row gy-3">
                                <div class="col-12">
                                    <div class="alert alert-warning" role="alert">
                                        <p>@lang('login.member_notice')</p>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="sr-only" for="login_email">@lang('register.email')</label>
                                    <input class="form-control w-100 @error('email') is-invalid @enderror"
                                           type="email"
                                           name="email"
                                           id="login_email"
                                           value="{{ old('email') }}"
                                           autocomplete="username"
                                           required
                                           autofocus
                                           placeholder="@lang('register.email')">
                                    @error('email')
                                        <span class="error_span" role="alert">{{ $message }}</span>
                                    @enderror
                                    @error('credentials')
                                        <span class="error_span" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label class="sr-only" for="login_password">@lang('register.password')</label>
                                    <input class="form-control w-100 @error('password') is-invalid @enderror"
                                           type="password"
                                           name="password"
                                           id="login_password"
                                           autocomplete="current-password"
                                           required
                                           placeholder="@lang('register.password')">
                                    @error('password')
                                        <span class="error_span" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-12 text-center">
                                    <button type="submit" class="btn-log-inscr">@lang('login.submit')</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endguest

{{-- `ux-header` is the hook ux.js toggles once the page has scrolled past the
     hero. The template positions this header absolutely over the hero, so it
     has to become an opaque bar at that point, or the links sit on top of a
     moving photograph and stop being readable. --}}
<div id="header" class="section exvent-header-section ux-header">
    <div class="container">
        <div class="row">
            <div class="col-12 header-toplinks">
                @auth
                    <a href="{{ route('account') }}" class="link-log-insc">@lang('account.title')</a>
                    <span class="header-toplinks-sep">|</span>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn-logout">@lang('action.logout')</button>
                    </form>
                @else
                    <a href="{{ route('register') }}" class="link-log-insc">@lang('action.register')</a>
                    <span class="header-toplinks-sep">|</span>
                    {{-- data-bs-target rather than an href="#": the button is a real
                         link so it is reachable by keyboard and works with scripting
                         off, and the modal is an enhancement layered on top. --}}
                    <a href="{{ route('login') }}"
                       class="link-log-insc"
                       data-bs-toggle="modal"
                       data-bs-target="#loginModal">@lang('action.login')</a>
                @endauth
            </div>
        </div>
    </div>

    <div class="container">
        <div class="header-wrap">

            {{-- The conference lockup for the active language, from the 2026
                 brand guidelines. The Arabic main version is much wider than
                 the Latin pair, so width/height come from Brand::logo() and the
                 CSS caps the height only: capping one dimension alone keeps
                 every version at its own aspect ratio. site_name is already
                 "ARABCIA 2026" in all three locales, so the alt text needs no
                 translation of its own. --}}
            <div class="header-logo">
                <a href="{{ route('home') }}">
                    <img src="{{ \App\Support\Brand::logoUrl() }}"
                         width="{{ \App\Support\Brand::logo()['width'] }}"
                         height="{{ \App\Support\Brand::logo()['height'] }}"
                         alt="{{ __('site.site_name') }}">
                </a>
            </div>

            <div class="header-menu d-none d-lg-block">
                @include('partials.nav')
            </div>

            <div class="header-meta">
                @auth
                    {{-- The cart icon points at the basket, not the order
                         history: it is the shopping symbol, and the order list
                         is one click away from the account page and from the
                         basket's own summary. The count is the number of places
                         in the basket, which is what the icon means on every
                         other shop the visitor has used. --}}
                    <div class="header-cart dropdown">
                        <a class="cart-btn" href="{{ route('cart') }}">
                            <i class="flaticon-shopping-cart" style="font-size: 22px;" aria-hidden="true"></i>
                            @php($basketCount = \App\Models\Cart::forSession(request()->session()->getId(), auth()->user())->items()->sum('quantity'))
                            @if ($basketCount > 0)
                                <span class="count">{{ $basketCount }}</span>
                            @endif
                            <span class="sr-only">@lang('order.cart.title')</span>
                        </a>
                    </div>
                @endauth

                <div class="header-btn d-none d-xl-block">
                    @if ($currentEdition?->registration_open)
                        <a href="{{ auth()->check() ? route('pricing') : route('register') }}"
                           class="font-display">@lang('nav.join')</a>
                    @endif
                </div>

                <div class="header-toggle d-lg-none">
                    <button type="button"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#mobileNav"
                            aria-controls="mobileNav"
                            aria-label="@lang('nav.menu')">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Mobile navigation. An offcanvas rather than a collapsed block, because that
     is what the template's toggle button is wired to, and because the desktop
     menu is display:none below the lg breakpoint so nothing is left stranded
     for a screen reader. --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="mobileNavLabel">{{ __('site.host_institute_short') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="@lang('nav.close')"></button>
    </div>
    <div class="offcanvas-body">
        @include('partials.nav', ['stacked' => true])

        <hr>

        <div class="d-flex flex-column gap-2 mt-3">
            @auth
                <a href="{{ route('cart') }}" class="btn btn-outline-secondary">@lang('order.cart.title')</a>
                <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">@lang('order.orders')</a>
                <a href="{{ route('account') }}" class="btn btn-primary">@lang('account.title')</a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger w-100">@lang('action.logout')</button>
                </form>
            @else
                <a href="{{ route('register') }}" class="btn btn-primary">@lang('action.register')</a>
                <a href="{{ route('login') }}" class="btn btn-outline-secondary">@lang('action.login')</a>
            @endauth
        </div>

        <div class="mt-4">
            @include('partials.locale-switcher')
        </div>
    </div>
</div>

@if ($errors->any() && request()->routeIs('login*') && auth()->guest())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var el = document.getElementById('loginModal');
            if (!el) return;
            var modal = bootstrap.Modal.getOrCreateInstance(el);
            modal.show();
        });
    </script>
@endif
