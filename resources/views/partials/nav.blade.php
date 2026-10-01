{{--
    Primary navigation, on the template's own markup.

    The 2024 build emitted an <li> per page with an `active-menu` class chosen by
    searching REQUEST_URI for the page's own filename. That cannot be ported as
    it stands: this app has two URLs per page (`/programme` and `/en/programme`),
    and a substring test against a filename would mark the wrong item active on
    both. request()->routeIs() asks the router which named route actually matched,
    which is the same question asked of something that knows the answer.

    The current page carries aria-current="page" as well as the class, so the
    highlight survives a high-contrast mode and is announced by a screen reader.

    `stacked` is the offcanvas variant, where the list is a column rather than a
    row. Both render from the same list so the two can never drift apart.
--}}
@php
    $items = [
        ['route' => 'home', 'label' => __('nav.home')],
        ['route' => 'programme', 'label' => __('nav.programme')],
        ['route' => 'speakers', 'label' => __('nav.speakers')],
        ['route' => 'pricing', 'label' => __('nav.pricing')],
        ['route' => 'venue', 'label' => __('nav.venue')],
        ['route' => 'sponsors', 'label' => __('nav.sponsors')],
        ['route' => 'contact', 'label' => __('nav.contact')],
        ['route' => 'archive', 'label' => __('nav.archive')],
    ];
@endphp

<ul @class(['main-menu', 'flex-column' => ($stacked ?? false)])
    @if ($stacked ?? false) aria-label="{{ __('nav.menu') }}" @endif>
    @foreach ($items as $item)
        @continue(! Route::has($item['route']))

        @php
            $isCurrent = request()->routeIs($item['route'].'*') || request()->routeIs($item['route']);
        @endphp

        <li @class(['active-menu' => $isCurrent])>
            <a href="{{ route($item['route']) }}"
               @if ($isCurrent) aria-current="page" @endif>
                {{ $item['label'] }}
            </a>
        </li>
    @endforeach

    {{-- Language is a menu item here, as in the 2024 build, rather than a
         separate control. The switcher posts to the server, so the dropdown holds
         the form rather than links — see partials.locale-switcher for why that
         is not the same as the 2024 markup. --}}
    @if (! ($stacked ?? false))
        <li class="dropdown">
            <a class="dropdown-lang-href" href="#" aria-haspopup="true" aria-expanded="false">
                @lang('misc.language')
            </a>
            <ul class="sub-menu">
                <li class="locale-switcher-item">
                    @include('partials.locale-switcher', ['inMenu' => true])
                </li>
            </ul>
        </li>
    @endif
</ul>
