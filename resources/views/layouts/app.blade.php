<!DOCTYPE html>
{{--
    The base layout.

    This is the 2024 site's own shell, rebuilt on the template's class names so
    the stylesheet in assets/css/style.css applies as written. Three things here
    are load-bearing rather than cosmetic:

    1. `dir` and `lang` come from the resolved Locale, not from a hardcoded
       string, and `rtl` is put on <body> as well because the template's own
       RTL rules are all scoped to `.rtl` rather than to `[dir=rtl]`. Getting
       either wrong leaves a page that reads left-to-right with the text ordered
       right-to-left, which is the exact failure the brief asks to avoid.

    2. The language switcher posts the current URL back to the server rather
       than guessing a counterpart path in JavaScript, so switching works with
       scripting disabled and the resulting URL is real.

    3. The stylesheets are linked from /assets rather than compiled by Vite.
       Bootstrap 5's reboot and Tailwind's preflight both reset the same
       properties, and loading both makes the rendered result depend on which
       stylesheet wins a tie — which is not a thing worth discovering in a
       browser. The template is the design; nothing else competes with it.
--}}
<html lang="{{ $currentLocale->value }}"
      dir="{{ $currentLocale->direction() }}"
      class="no-js">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $currentEdition?->titleIn($currentLocale) ?? __('site.site_name'))</title>
    <meta name="description" content="@yield('description', __('meta.description', ['year' => $currentEdition?->year ?? date('Y')]))">

    {{-- The three language variants are the same page, and French — the
         unprefixed form — is the canonical one.

         The alternates are generated from the current route's canonical name
         rather than from url()->current(), because the current URL is by
         definition the wrong language for two of the three entries. The `.fr`
         suffix is stripped: it is there only to keep route:list readable, and
         passing it to route() would ask for a URL that does not exist.

         Skipped on POST, where there is no GET address for the page and a
         referer-derived URL would be a guess. --}}
    @php
        $currentRouteName = request()->route()?->getName();
        $canonicalRouteName = $currentRouteName ? str_ends_with($currentRouteName, '.fr')
            ? substr($currentRouteName, 0, -3)
            : $currentRouteName
            : null;
    @endphp
    @if ($currentEdition && $canonicalRouteName && request()->isMethod('GET'))
        <link rel="canonical" href="{{ url()->current() }}">
        @foreach (array_keys($availableLocales ?? []) as $code)
            @if ($code !== $currentLocale->value && Route::has($canonicalRouteName))
                <link rel="alternate" hreflang="{{ $code }}"
                      href="{{ route($canonicalRouteName, ['locale' => $code]) }}">
            @endif
        @endforeach
    @endif

    {{-- Favicons, carried over from the 2024 build. --}}
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/images/favicon/favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/images/favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/images/favicon/favicon-16x16.png') }}">

    {{-- Icon fonts: Font Awesome 5 and the template's own flaticon set. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/flaticon.css') }}">

    {{-- Vendor CSS. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/aos.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/lightbox.min.css') }}">

    {{-- The design itself, then the RTL pass, then the application layer. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    @if ($isRtl ?? false)
        <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">

    {{-- The experience layer, last of all: motion, depth and light. It composes
         on top of the design rather than replacing it, and every rule that moves
         something respects prefers-reduced-motion. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/ux.css') }}">

    @stack('head')
</head>
<body class="{{ ($isRtl ?? false) ? 'rtl' : '' }}">

{{-- Scroll progress. Sits above everything, including the header, and is driven
     by ux.js through a custom property. It has no content of its own, so it is
     hidden from assistive technology rather than announced on every scroll. --}}
<div class="ux-progress" role="presentation" aria-hidden="true"></div>

<div class="main-wrapper">

    @include('partials.header')

    <main id="main">
        @include('partials.flash')

        @yield('content')
    </main>

    @include('partials.footer')

    {{-- Back to top. The 2024 build shipped this after the footer rather than
         inside it, so it is positioned against the page, not the footer. --}}
    <div class="progress-wrap">
        <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"/>
        </svg>
    </div>

</div>

{{-- Vendor JS. jQuery first: the template's own scripts and the plugins all
     assume it, and several use the 1.x API. --}}
<script src="{{ asset('assets/js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('assets/js/vendor/modernizr-3.11.2.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/swiper-bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/aos.js') }}"></script>
<script src="{{ asset('assets/js/plugins/waypoints.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/back-to-top.js') }}"></script>
<script src="{{ asset('assets/js/plugins/jquery.counterup.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/appear.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/jquery.magnific-popup.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/lightbox.min.js') }}"></script>
<script src="{{ asset('assets/js/main.js') }}"></script>

{{-- The experience layer. Vanilla, dependency-free, and strictly additive: if
     it fails to load the site is unchanged. It is loaded last so it sees the
     final DOM and so nothing it adds is removed by a plugin that assumes it
     owns the page. --}}
<script src="{{ asset('assets/js/ux.js') }}" defer></script>

@stack('scripts')
</body>
</html>
