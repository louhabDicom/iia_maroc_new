{{--
    Shown when no edition row is marked current and published.

    This is a data-entry state, not an error: the organisers load the site before
    the edition row is published. Rendering a framework 500 here would be alarming
    and would leak a stack trace if APP_DEBUG were ever left on by mistake, so the
    site says plainly that it is not open yet.

    It is built from the same vocabulary as every other page rather than from a
    bespoke centred block. That is not decoration: a visitor who lands here on the
    evening the site goes live sees a page that looks broken rather than closed,
    and the first thing they should take from it is that it is the same site.
--}}
@extends('layouts.app')

@section('title', __('site.site_name'))
@section('description', __('state.coming_soon'))

@section('content')
    <x-page-hero
        :title="__('site.site_name')"
        :eyebrow="null"
        :lede="__('state.coming_soon')"
        :crumbs="[]"
        image="assets/images/bg/price_bg.jpg" />

    <section class="ux-section section-padding-04 ux-section--defer">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7 col-md-9">

                    <div class="ux-card ux-card--glass ux-radius-xl p-5 text-center ux-reveal ux-reveal-scale">

                        {{-- The same locale-aware lockup as the header, so the page
                             still reads as branded while an edition is closed. --}}
                        <img src="{{ \App\Support\Brand::logoUrl() }}"
                             width="{{ \App\Support\Brand::logo()['width'] }}"
                             height="{{ \App\Support\Brand::logo()['height'] }}"
                             alt="{{ __('site.site_name') }}"
                             class="mb-4"
                             style="max-height: 90px;">

                        <h1 class="ux-card__title h3 mb-3">@lang('state.coming_soon')</h1>

                        <p class="ux-ink-soft mb-4">@lang('state.coming_soon_lede')</p>

                        {{-- A way out rather than a dead end. The contact address is
                             the one thing that is always real, even on the evening
                             before the programme is published. --}}
                        <a href="{{ route('contact') }}" class="btn btn-primary">
                            @lang('nav.contact')
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
