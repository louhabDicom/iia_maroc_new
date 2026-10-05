<!DOCTYPE html>
{{--
    The staff shell.

    Deliberately not the public layout. It drops the site header, the footer and
    the language switcher and replaces them with a fixed side rail, because the
    two audiences have opposite needs: a visitor scrolls one page and leaves, an
    operator switches between six lists all day and wants the current one always
    in the same place.

    What is kept from the public layout is the parts that are correctness rather
    than decoration:

      * `dir` and `lang` still come from the resolved Locale, and `rtl` is still
        on <body>, because the template's RTL rules are scoped to `.rtl`. An
        Arabic-speaking operator looking at an order with a French name needs the
        column of numbers in the right order, which is the whole point.

      * The flash partial is included at the top of <main>, so a message survives
        whatever the redirect lands on.

      * The same stylesheets, in the same order. The admin CSS loads after them
        and only adds; it never overrides a public rule, so an operator and a
        delegate are looking at the same type scale and the same accents.

    `$currentRoute` is set by every admin controller and is what marks the
    active rail item. It is compared against the route name exactly, rather than
    with `str_starts_with`, because `admin.orders.index` and `admin.orders.show`
    are the same section and an operator opening an order should still see
    "Orders" lit up.
--}}
<html lang="{{ $currentLocale->value }}"
      dir="{{ $currentLocale->direction() }}"
      class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Staff pages are not indexed and not shareable. An order list indexed
         by a search engine is a data leak with a delay. --}}
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', __('admin.title')) — @lang('admin.title')</title>

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/images/favicon/favicon.ico') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/plugins/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/flaticon.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    @if ($isRtl ?? false)
        <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ux.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">

    @stack('head')
</head>
<body class="admin-body {{ ($isRtl ?? false) ? 'rtl' : '' }}">

<a href="#admin-main" class="admin-skip">@lang('admin.skip_to_content')</a>

<div class="admin-shell">

    {{-- The rail. A <nav> with an accessible name, and the current item marked
         with aria-current rather than only with a background colour, so the
         position is announced and not merely drawn. --}}
    {{-- Escaped, not `@lang`: the French label contains an apostrophe
         ("Navigation de l'administration"), and writing it into an attribute raw
         closes the quote early — which silently swallows the rest of the document
         for any HTML parser, screen reader included. --}}
    <nav class="admin-rail" aria-label="{{ __('admin.nav_label') }}">
        <div class="admin-rail__brand">
            <a href="{{ route('admin.dashboard') }}">
                <span class="ux-eyebrow">@lang('admin.title')</span>
                <strong>{{ $currentEdition?->year ?? date('Y') }}</strong>
            </a>
        </div>

        <ul class="admin-rail__list">
            @php
                // Queue counts are passed by the dashboard only. On the other
                // pages the badge is omitted rather than shown as zero: an
                // explicit "0" next to a section that has nothing pending is
                // noise, and a missing badge is indistinguishable from a
                // deliberate zero.
                $counts = $queues ?? [];
            @endphp

            @foreach ([
                ['route' => 'admin.dashboard',      'icon' => 'fa-chart-pie',     'label' => 'admin.nav.dashboard'],
                ['route' => 'admin.orders.index',   'icon' => 'fa-receipt',       'label' => 'admin.nav.orders',    'badge' => 'orders'],
                ['route' => 'admin.participants.index', 'icon' => 'fa-id-badge', 'label' => 'admin.nav.participants'],
                ['route' => 'admin.submissions.index', 'icon' => 'fa-microphone',  'label' => 'admin.nav.submissions', 'badge' => 'submissions'],
                ['route' => 'admin.enquiries.index',   'icon' => 'fa-handshake',   'label' => 'admin.nav.enquiries',   'badge' => 'enquiries'],
                ['route' => 'admin.messages.index',    'icon' => 'fa-envelope',     'label' => 'admin.nav.messages',    'badge' => 'messages'],
            ] as $item)
                <li>
                    <a href="{{ route($item['route']) }}"
                       @if (($currentRoute ?? null) === $item['route']) aria-current="page" @endif
                       @class(['admin-rail__link', 'is-current' => ($currentRoute ?? null) === $item['route']])>
                        <i class="fas {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span>@lang($item['label'])</span>

                        @isset($item['badge'])
                            @php $n = $counts[$item['badge']]['count'] ?? 0; @endphp
                            @if ($n > 0)
                                {{-- The count is inside the link's accessible name
                                     via the visually-hidden prefix, and the numeral
                                     itself is aria-hidden: "12" read cold tells an
                                     operator nothing about what it counts. --}}
                                <span class="admin-rail__badge" aria-hidden="true">{{ $n }}</span>
                                <span class="visually-hidden">@lang('admin.nav.pending', ['count' => $n])</span>
                            @endif
                        @endisset
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="admin-rail__foot">
            <a href="{{ route('home') }}" class="admin-rail__link">
                <i class="fas fa-long-arrow-alt-left" aria-hidden="true"></i>
                <span>@lang('admin.nav.public_site')</span>
            </a>

            {{-- POST, not a link: signing out must not be a GET, or a browser's
                 prefetch or a scanner following links could end an operator's
                 session. --}}
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="admin-rail__link admin-rail__link--button">
                    <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                    <span>@lang('action.logout')</span>
                </button>
            </form>
        </div>
    </nav>

    <main id="admin-main" class="admin-main" tabindex="-1">
        <header class="admin-topbar">
            <h1 class="admin-topbar__title">@yield('heading', __('admin.title'))</h1>
            @yield('actions')
        </header>

        @include('partials.flash')

        @yield('content')
    </main>

</div>

{{-- Bootstrap's bundle only; none of the public site's plugins are loaded,
     because every one of them assumes a public page exists and several of them
     bind to elements that are not on any admin page. --}}
<script src="{{ asset('assets/js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/js/ux.js') }}" defer></script>

@stack('scripts')
</body>
</html>