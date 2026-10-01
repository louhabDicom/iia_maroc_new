{{--
    Inner page header.

    The template shipped a `.page-title` rule but no page used it, so this band
    is new. It is built from the template's own pieces — .exvent-hero-section's
    background treatment, .section-title, and the .breadcrumb styles the
    template's commented-out search block implies — so a page heading is the
    same visual object as a home-page heading, only shorter.

    The breadcrumb is generated from the route's locale segment rather than
    hand-written per page, so a new page gets a correct one for free and cannot
    claim to be under a section it is not in. The last crumb is the current page
    and is marked aria-current, which is what makes it read as a position rather
    than as another link.
--}}
@props([
    'title',
    'subTitle' => null,
    'image' => 'assets/images/bg/about_page_bg.jpg',
    'crumbs' => [],
])

<section class="page-hero" style="background-image: url('{{ asset($image) }}');">
    <div class="container">
        <div class="section-title">
            @if ($subTitle)
                <h5 class="sub-title white-2">{{ $subTitle }}</h5>
            @endif
            <h1 class="title white">{{ $title }}</h1>
        </div>

        @if ($crumbs !== [])
            <nav class="breadcrumb-area" aria-label="{{ __('nav.menu') }}">
                <ol class="breadcrumb">
                    <li><a href="{{ route('home') }}">@lang('nav.home')</a></li>
                    @foreach ($crumbs as $label => $url)
                        <li @if ($loop->last) aria-current="page" @endif>
                            @if ($loop->last || ! $url)
                                {{ $label }}
                            @else
                                <a href="{{ $url }}">{{ $label }}</a>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif
    </div>
</section>
