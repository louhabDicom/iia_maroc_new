@extends('layouts.app')

@section('title', __('speakers.title'))

@section('content')

    <x-page-hero
        :title="__('speakers.title')"
        :crumbs="[__('nav.speakers') => null]"
        image="assets/images/bg/speaker_bg_h4.jpg" />

    <div class="speaker-section section-padding-03">
        <div class="container">
            {{-- Stated up front because it is true: the brief confirms the 2026 line-up
                 is provisional. Presenting unconfirmed names as final is how a
                 speaker withdraws and the site is left wrong. --}}
            <p class="notice-provisional text-center">@lang('speakers.subtitle')</p>

            @if ($keynotes->isEmpty() && $speakers->isEmpty())
                <p class="empty-state mt-4">@lang('state.empty')</p>
            @endif

            @if ($keynotes->isNotEmpty())
                <section class="mt-4" aria-labelledby="keynotes-heading">
                    <div class="section-title text-center">
                        <h2 class="title" id="keynotes-heading">@lang('speakers.keynotes')</h2>
                    </div>

                    <ul class="row g-4 mt-2 list-unstyled">
                        @foreach ($keynotes as $speaker)
                            <div class="col-lg-6 col-12">
                                @include('partials.speaker-card', ['speaker' => $speaker, 'large' => true])
                            </div>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($speakers->isNotEmpty())
                <section class="mt-5" aria-labelledby="speakers-heading">
                    <h2 id="speakers-heading" class="visually-hidden">@lang('speakers.title')</h2>

                    <ul class="row g-4 list-unstyled">
                        @foreach ($speakers as $speaker)
                            <div class="col-lg-4 col-md-6 col-12">
                                @include('partials.speaker-card', ['speaker' => $speaker])
                            </div>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>

@endsection
