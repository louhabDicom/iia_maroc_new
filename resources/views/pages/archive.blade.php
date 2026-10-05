@extends('layouts.app')

@section('title', __('archive.title'))
@section('description', __('archive.intro'))

@section('content')
    <x-front.hero
        :title="__('archive.title')"
        :crumbs="[__('nav.archive') => null]"
        image="assets/images/bg/about_page_bg.jpg" />

    {{-- The record of an edition: figures, then the documents.

         The year switcher is a row of tags rather than a select. The archive holds
         a handful of years, every one of them a one-tap destination, and a select
         on a phone costs a tap and a scroll to show a list of four. --}}
    <section class="ux-section section-padding-03 ux-section--defer"
             aria-labelledby="archive-record-heading">
        <div class="container">

            @if ($editions->isNotEmpty())
                <nav class="ux-tags mb-5" aria-label="{{ __('archive.title') }}">
                    @foreach ($editions as $year)
                        <a href="{{ route('archive', ['locale' => request()->route('locale'), 'year' => $year->year]) }}"
                           @class([
                               'ux-tag',
                               'ux-tag--solid' => $archived?->year === $year->year,
                               'ux-tag--muted' => $archived?->year !== $year->year,
                           ])
                           @if ($archived?->year === $year->year) aria-current="page" @endif>
                            {{ $year->year }}
                        </a>
                    @endforeach
                </nav>
            @endif

            @if ($archived === null)
                <x-empty-state :message="__('state.empty')" icon="fa-box-archive" />
            @else
                <div class="row g-4">

                    <div class="col-lg-5 ux-reveal ux-reveal-left">
                        <div class="ux-card ux-card--glass ux-radius-xl p-4 h-100">
                            <x-section-head
                                id="archive-record-heading"
                                :eyebrow="__('archive.title')"
                                :title="$archived->titleIn($locale)"
                                :level="2" />

                            <p class="ux-ink-soft mb-0">
                                {{ $archived->dateLine($locale) }}<br>
                                {{ $archived->venueLine($locale) }}
                            </p>

                            @if ($stats)
                                {{-- A <dl> rather than two loose figures: the number
                                     is meaningless without the thing it counts. --}}
                                <dl class="row g-3 mt-4 mb-0">
                                    <div class="col-6">
                                        <dt class="ux-ink-soft small">@lang('programme.title')</dt>
                                        <dd class="ux-stat mb-0">{{ $stats['sessions'] }}</dd>
                                    </div>
                                    <div class="col-6">
                                        <dt class="ux-ink-soft small">@lang('speakers.title')</dt>
                                        <dd class="ux-stat mb-0">{{ $stats['speakers'] }}</dd>
                                    </div>
                                </dl>
                            @endif
                        </div>
                    </div>

                    <div class="col-lg-7 ux-reveal ux-reveal-right">
                        {{-- isAvailableIn() checks the per-locale flag, so a document
                             translated only into French does not 404 for an Arabic
                             visitor. --}}
                        <div class="ux-card ux-card--edge ux-radius-xl p-4 h-100">
                            <x-section-head
                                :title="__('archive.documents')"
                                :level="2" />

                            @if ($archived->archive_note)
                                <div class="ux-notice ux-notice--warning mb-4">
                                    {{ $archived->archive_note }}
                                </div>
                            @endif

                            @if ($documents->isEmpty())
                                <p class="ux-ink-soft mb-0">@lang('state.coming_soon')</p>
                            @else
                                <ul class="ux-checks list-unstyled mb-0">
                                    @foreach ($documents as $document)
                                        @if ($document->isAvailableIn($locale->value))
                                            <li class="d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
                                                <span class="ux-ink">{{ $document->title }}</span>
                                                <a href="{{ $document->downloadUrl() }}" class="ux-btn ux-btn--ghost">
                                                    @lang('action.download')
                                                    @if ($document->formattedSize())
                                                        <span class="ux-ink-soft small">
                                                            ({{ $document->formattedSize() }})
                                                        </span>
                                                    @endif
                                                </a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

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

@endsection
