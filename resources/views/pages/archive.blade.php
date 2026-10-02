@extends('layouts.app')

@section('title', __('archive.title'))
@section('description', __('archive.intro'))

@section('content')
    <x-page-hero
        :title="__('archive.title')"
        :crumbs="[__('nav.archive') => null]"
        image="assets/images/bg/about_page_bg.jpg" />

    <div class="section-padding-04">
        <div class="container">
            <div class="app-shell app-shell--wide">

                <p class="app-lede">@lang('archive.intro')</p>

        {{-- Year switcher, built from the editions table. A year that does not
             exist yields null below rather than an empty page, because
             Edition::archive() resolves against real rows. --}}
        @if ($editions->isNotEmpty())
            <nav class="d-flex flex-wrap gap-2 mb-4" aria-label="{{ __('archive.title') }}">
                @foreach ($editions as $year)
                    <a href="{{ route('archive', ['locale' => request()->route('locale'), 'year' => $year->year]) }}"
                       @class([
                           'app-badge',
                           'app-badge--brand' => $archived?->year === $year->year,
                           'app-badge--muted' => $archived?->year !== $year->year,
                       ])
                       @if ($archived?->year === $year->year) aria-current="page" @endif>
                        {{ $year->year }}
                    </a>
                @endforeach
            </nav>
        @endif

        @if ($archived === null)
            <div class="app-empty">@lang('state.empty')</div>
        @else
            <section class="app-card">
                <h2 class="app-title app-title--sm">
                    {{ $archived->titleIn($locale) }}
                </h2>

                <p class="app-note">
                    {{ $archived->dateLine($locale) }}<br>
                    {{ $archived->venueLine($locale) }}
                </p>

                @if ($archived->archive_note)
                    <div class="app-notice mt-4">{{ $archived->archive_note }}</div>
                @endif

                @if ($stats)
                    <dl class="d-flex flex-wrap gap-5 mt-4 mb-0">
                        <div>
                            <dt class="app-note">@lang('programme.title')</dt>
                            <dd class="app-title app-title--sm mb-0">{{ $stats['sessions'] }}</dd>
                        </div>
                        <div>
                            <dt class="app-note">@lang('speakers.title')</dt>
                            <dd class="app-title app-title--sm mb-0">{{ $stats['speakers'] }}</dd>
                        </div>
                    </dl>
                @endif
            </section>

            {{-- Downloads. isAvailableIn() checks the per-locale flag, so a
                 document that was only translated into French does not 404 for
                 an Arabic visitor. --}}
            <section class="app-section" aria-labelledby="archive-documents">
                <h2 id="archive-documents" class="app-section__title">
                    @lang('archive.documents')
                </h2>

                @if ($documents->isEmpty())
                    <p class="app-note">@lang('state.coming_soon')</p>
                @else
                    <ul class="app-list">
                        @foreach ($documents as $document)
                            @if ($document->isAvailableIn($locale->value))
                                <li class="app-list__row">
                                    <span class="app-note--strong">{{ $document->title }}</span>
                                    <a href="{{ $document->downloadUrl() }}"
                                       class="app-link">
                                        @lang('action.download')
                                        @if ($document->formattedSize())
                                            <span class="app-note">({{ $document->formattedSize() }})</span>
                                        @endif
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif

    {{-- The editions, as photographs.

         The archive is otherwise a list of dates and figures, which is exactly
         what a table is for and exactly what does not make anyone want to come
         back next year. These are the organisers' own photographs of the
         conferences that came before. --}}
    @php
        $archivePhotos = array_values(array_filter(array_map(
            static fn (int $index): ?string => file_exists(public_path("assets/images/conference/previous-editions-0{$index}.jpg"))
                ? "assets/images/conference/previous-editions-0{$index}.jpg"
                : null,
            range(1, 6),
        )));
    @endphp

    @if ($archivePhotos !== [])
        <section class="ux-section section-padding-03" aria-labelledby="archive-gallery-heading">
            <div class="container">

                <x-section-head
                    id="archive-gallery-heading"
                    :eyebrow="__('nav.archive')"
                    :title="__('archive.gallery_title')"
                    :lede="__('archive.gallery_lede')"
                    :level="2"
                    align="center"
                    class="mb-5" />

                <ul class="editions-gallery__grid list-unstyled" data-ux-stagger="60">
                    @foreach ($archivePhotos as $index => $photo)
                        <li class="editions-gallery__item ux-reveal ux-reveal-scale"
                            style="--ux-reveal-delay: {{ $index * 60 }}ms">
                            <a href="{{ asset($photo) }}"
                               data-lightbox="archive"
                               data-caption="{{ __('home.editions_caption', ['number' => $index + 1]) }}">
                                <img src="{{ asset($photo) }}"
                                     alt="{{ __('home.editions_alt', ['number' => $index + 1]) }}"
                                     width="613"
                                     height="408"
                                     loading="lazy"
                                     decoding="async">
                                <span class="editions-gallery__zoom" aria-hidden="true">
                                    <i class="fas fa-expand"></i>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    </div>
@endsection
