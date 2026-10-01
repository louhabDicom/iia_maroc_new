{{--
    Shown when no edition row is marked current and published.

    This is a data-entry state, not an error: the organisers load the site
    before the edition row is published. Rendering a framework 500 here would be
    alarming and would leak a stack trace if APP_DEBUG were ever left on by
    mistake, so the site says plainly that it is not open yet.
--}}
@extends('layouts.app')

@section('title', __('site.site_name'))

@section('content')
    <div class="section-padding-05">
        <div class="container">
            <div class="empty-state">
                <img src="{{ asset('assets/images/logo/logo-iia-maroc.jpg') }}"
                     alt="{{ __('site.host_institute_short') }}"
                     style="max-height: 90px; margin-bottom: 24px;">

                <h1 class="title" style="font-size: 34px; color: var(--arab-heading);">
                    {{ __('site.site_name') }}
                </h1>
                <p class="mt-3 mb-0">@lang('state.coming_soon')</p>
            </div>
        </div>
    </div>
@endsection
