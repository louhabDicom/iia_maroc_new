@extends('layouts.app')

@section('title', $edition->titleIn($locale))
@section('description', $edition->introduction)

@section('content')

    {{-- Hero. Dates and venue come from the edition, never hardcoded.

         The 2024 hero was a photograph with a heading laid over it and little
         else. This keeps the same photograph and the same facts, but treats the
         hero as the one screen that has to earn attention in three seconds: a
         large headline, one clear primary action, a secondary action, and the
         three figures the brief names.
    --}}
    <section class="ux-hero section exvent-hero-section"
             style="background-image: url({{ asset('assets/images/bg/hero_bg1.jpg') }});">

        {{-- The drifting colour fields. Purely decorative, so they are hidden
             from assistive technology rather than announced as content. --}}
        <div class="ux-aurora" aria-hidden="true">
            <div class="ux-aurora__field ux-aurora__field--magenta" data-ux-parallax="0.10"></div>
            <div class="ux-aurora__field ux-aurora__field--violet" data-ux-parallax="0.16"></div>
            <div class="ux-aurora__field ux-aurora__field--gold" data-ux-parallax="0.07"></div>
            <div class="ux-aurora__grain"></div>
        </div>

        <div class="container position-relative">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="hero-content py-5">

                        <p class="ux-hero__eyebrow ux-reveal">
                            {{ $edition->starts_on->translatedFormat('d') }}&ndash;{{ $edition->ends_on->translatedFormat('d M Y') }}
                        </p>

                        <h1 class="ux-hero__title ux-reveal">
                            {{ $edition->theme ?? $edition->titleIn($locale) }}
                        </h1>

                        <p class="ux-hero__lede ux-reveal">
                            {{ $edition->venueLine($locale) }}
                        </p>

                        @if ($edition->introduction)
                            <p class="ux-hero__lede ux-reveal">
                                {!! nl2br(e(\Illuminate\Support\Str::limit($edition->introduction, 190))) !!}
                            </p>
                        @endif

                        <div class="ux-hero__actions ux-reveal">
                            @if ($edition->registration_open)
                                <a href="{{ auth()->check() ? route('pricing') : route('register') }}"
                                   class="ux-btn ux-btn--primary"
                                   data-ux-magnetic="0.22">
                                    <span>@lang('home.hero_cta')</span>
                                </a>
                            @endif

                            <a href="{{ route('programme') }}"
                               class="ux-btn ux-btn--on-dark"
                               data-ux-magnetic="0.18">
                                <span>@lang('home.hero_secondary')</span>
                            </a>
                        </div>

                    </div>
                </div>

                {{-- The photograph, kept as a real element so it can be framed
                     and lifted rather than sitting flat in the background. It is
                     decorative: the same information is in the text above. --}}
                <div class="col-lg-5">
                    <div class="hero-images d-none d-lg-block">
                        <div class="images ux-card ux-card--glass ux-card--flush ux-reveal ux-reveal-scale ux-tilt"
                             data-ux-tilt="5">
                            <img src="{{ asset('assets/images/hero_img1.jpg') }}"
                                 class="slide-img w-100"
                                 alt=""
                                 loading="eager"
                                 decoding="async">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- A soft fade into the next section, so the hard edge between the hero
             and the page below stops looking like a seam. --}}
        <div class="ux-hero__fade" aria-hidden="true"></div>
    </section>

    {{-- Event figures.

         The values are counted from the published programme by HomeController,
         not hardcoded here, so this block can never claim a number the
         programme page contradicts. ux.js animates them from zero; with
         scripting off they are simply the final value. --}}
    <div class="event-counter-section section-padding" style="background:var(--ux-surface-alt);">
        <div class="container">
            <div class="row align-items-center g-5">

                <div class="col-lg-4 col-12">
                    <div class="section-title ux-reveal ux-reveal-left">
                        <h2 class="title">@lang('home.join_us')</h2>
                        <p class="ux-section-lede">
                            {{ \Illuminate\Support\Str::limit($edition->introduction, 150) }}
                        </p>
                        <a href="{{ route('pricing') }}" class="ux-btn ux-btn--primary" data-ux-magnetic="0.2">
                            <span>@lang('home.hero_cta')</span>
                        </a>
                    </div>
                </div>

                <div class="col-lg-8 col-12">
                    <div class="row g-4" data-ux-stagger="90">
                        @foreach ([
                            ['value' => $stats['attendees'], 'label' => __('home.stats.attendees')],
                            ['value' => $stats['workshops'], 'label' => __('home.stats.workshops')],
                            ['value' => $stats['days'], 'label' => __('home.stats.days')],
                        ] as $stat)
                            <div class="col-md-4">
                                <div class="ux-stat ux-reveal ux-reveal-scale"
                                     data-ux-tilt="4">
                                    <span class="ux-stat__value">{{ number_format($stat['value']) }}</span>
                                    <span class="ux-stat__label">{{ $stat['label'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- About / introduction. --}}
    @if ($edition->introduction)
        <div class="about-section section-padding-03">
            <div class="container">
                <div class="row g-0">
                    <div class="col-lg-5 order-lg-1 order-2">
                        <div class="about-thumb-wrap">
                            <img src="{{ asset('assets/images/about_img1.jpg') }}" alt="">
                            <img src="{{ asset('assets/images/about_img2.jpg') }}" alt="">
                        </div>
                    </div>
                    <div class="col-lg-7 order-lg-1 order-1">
                        <div class="about-content-wrap">
                            <img src="{{ asset('assets/images/shape/about_content_shape1.png') }}" alt="" class="about-box-shape">
                            <img src="{{ asset('assets/images/shape/about_shape1.png') }}" alt="" class="about-shape-x">
                            <div class="section-title">
                                <h5 class="sub-title">@lang('home.about_subtitle')</h5>
                                <h2 class="title">@lang('home.about_title')</h2>
                            </div>
                            <div class="section-paragraph">
                                <p>{!! nl2br(e($edition->introduction)) !!}</p>
                            </div>
                            @if (! empty($edition->target_audience))
                                <div class="section-paragraph mt-4">
                                    <ul>
                                        @foreach ($edition->target_audience as $audience)
                                            <li>{{ $audience }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <div class="about-btn">
                                <a class="btn" href="{{ route('contact') }}">@lang('home.contact_cta')</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Video. The 2024 build embedded a local MP4. Kept as-is if it exists,
         otherwise hidden to avoid a 404. --}}
    @php
        $videoPath = public_path('assets/videos/Capsule.mp4');
    @endphp
    @if (file_exists($videoPath))
        <div class="container-fluid cont-video">
            <div class="row justify-content-center">
                <div class="col-md-7 col-12">
                    <video src="{{ asset('assets/videos/Capsule.mp4') }}"
                           controls
                           poster="{{ asset('assets/videos/video-background.png') }}"
                           preload="metadata"></video>
                </div>
            </div>
        </div>
    @endif

    {{-- Speakers preview. --}}
    @if ($speakers->isNotEmpty())
        <div class="speaker-section section-padding-top">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="section-title text-center">
                            <h2 class="title white">@lang('home.speakers_title')</h2>
                        </div>
                    </div>
                </div>
                <div class="row g-0 justify-content-center">
                    @foreach ($speakers as $speaker)
                        <div class="col-lg-3 col-sm-6">
                            <div class="single-speaker speaker-card-link h-100">
                                <div class="speaker-thumb">
                                    @if ($speaker->photo_path)
                                        <img src="{{ \Illuminate\Support\Str::startsWith($speaker->photo_path, 'http')
                                                ? $speaker->photo_path
                                                : asset('storage/'.$speaker->photo_path) }}"
                                             alt="{{ $speaker->fullName() }}"
                                             loading="lazy">
                                    @else
                                        <div class="speaker-card__empty" aria-hidden="true">—</div>
                                    @endif
                                </div>
                                <div class="speaker-content">
                                    <h4>{{ $speaker->fullName() }}</h4>
                                    @if ($speaker->organisation)
                                        <span>{{ $speaker->organisation }}</span>
                                    @endif
                                    @if ($speaker->job_title)
                                        <small class="d-block">{{ $speaker->job_title }}</small>
                                    @endif
                                </div>
                                <div class="speaker-shape">
                                    <img src="{{ asset('assets/images/shape/speaker_shape1.png') }}" alt="">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Programme preview (optional). Render a minimal summary if sessions exist. --}}
    @if ($sessions->isNotEmpty())
        <div class="schedule-area section-padding-02 grey-bg">
            <img src="{{ asset('assets/images/shape/schedule_shape1.png') }}" class="schedule-shape1" alt="">
            <img src="{{ asset('assets/images/shape/schedule_shape2.png') }}" class="schedule-shape2" alt="">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="section-title-wrap">
                            <div class="section-title text-center">
                                <h2 class="title">@lang('home.programme_title')</h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <ul class="list-unstyled">
                            @foreach ($sessions->take(4) as $session)
                                <li class="py-3 border-bottom">
                                    <strong>{{ $session->title }}</strong>
                                    <div class="text-muted small">
                                        {{ $session->session_date->translatedFormat('D j M') }}
                                        &middot; {{ $session->startsAtString() }} – {{ $session->endsAtString() }}
                                        @if ($session->format) &middot; {{ $session->format->label($locale->value) }} @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <div class="text-center mt-4">
                            <a href="{{ route('programme') }}" class="btn">@lang('home.hero_secondary')</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Sponsors (brand). --}}
    @if ($sponsors->isNotEmpty())
        <div class="brand-area section-padding-05">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="section-title-wrap">
                            <div class="section-title text-center">
                                <h5 class="sub-title">@lang('home.sponsors_title')</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row justify-content-center text-center align-items-center g-4">
                    @foreach ($sponsors as $sponsor)
                        <div class="col-md-3 col-6">
                            <div class="brand-item">
                                <x-sponsor-logo :sponsor="$sponsor" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Registration CTA. --}}
    @if ($edition->registration_open)
        <div class="pricing-area section-padding-04" id="inscription">
            <div class="pricing-wrapper">
                <div class="container">
                    <div class="row">
                        <div class="col-12">
                            <div class="section-title-wrap">
                                <div class="section-title text-center">
                                    <h5 class="sub-title text-white">@lang('pricing.title')</h5>
                                    <h2 class="title white">@lang('home.registration_title')</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row justify-content-center mt-4">
                        <div class="col-auto">
                            <a href="{{ route('register') }}" class="btn btn-light">@lang('home.hero_cta')</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="container mt-5">
            <p class="alert alert-info text-center">@lang('pricing.closed')</p>
        </div>
    @endif

@endsection
