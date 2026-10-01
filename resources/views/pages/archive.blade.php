@extends('layouts.app')

@section('title', __('archive.title'))
@section('description', __('archive.intro'))

@section('content')
    <div class="mx-auto max-w-4xl">

        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
            @lang('archive.title')
        </h1>
        <p class="mt-2 text-slate-600 dark:text-slate-400">@lang('archive.intro')</p>

        {{-- Year switcher, built from the editions table. A year that does not
             exist yields null below rather than an empty page, because
             Edition::archive() resolves against real rows. --}}
        @if ($editions->isNotEmpty())
            <nav class="mt-6 flex flex-wrap gap-2" aria-label="{{ __('archive.title') }}">
                @foreach ($editions as $year)
                    <a href="{{ route('archive', ['locale' => request()->route('locale'), 'year' => $year->year]) }}"
                       @class([
                           'rounded-full px-4 py-1.5 font-mono text-sm font-medium transition',
                           'bg-brand text-white' => $archived?->year === $year->year,
                           'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' => $archived?->year !== $year->year,
                       ])
                       @if ($archived?->year === $year->year) aria-current="page" @endif>
                        {{ $year->year }}
                    </a>
                @endforeach
            </nav>
        @endif

        @if ($archived === null)
            <p class="mt-12 rounded-lg border border-slate-200 bg-slate-50 px-6 py-10 text-center text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
                @lang('state.empty')
            </p>
        @else
            <section class="mt-10 rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ $archived->titleIn($locale) }}
                </h2>

                <p class="mt-2 text-slate-600 dark:text-slate-400">
                    {{ $archived->dateLine($locale) }}<br>
                    {{ $archived->venueLine($locale) }}
                </p>

                @if ($archived->archive_note)
                    <p class="mt-4 border-s-2 border-brand-300 ps-4 text-sm text-slate-600 dark:border-brand-800 dark:text-slate-400">
                        {{ $archived->archive_note }}
                    </p>
                @endif

                @if ($stats)
                    <dl class="mt-6 flex flex-wrap gap-8">
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-slate-400">@lang('programme.title')</dt>
                            <dd class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['sessions'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-slate-400">@lang('speakers.title')</dt>
                            <dd class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['speakers'] }}</dd>
                        </div>
                    </dl>
                @endif
            </section>

            {{-- Downloads. isAvailableIn() checks the per-locale flag, so a
                 document that was only translated into French does not 404 for
                 an Arabic visitor. --}}
            <section class="mt-10" aria-labelledby="archive-documents">
                <h2 id="archive-documents" class="text-xl font-semibold text-slate-900 dark:text-white">
                    @lang('archive.documents')
                </h2>

                @if ($documents->isEmpty())
                    <p class="mt-4 text-slate-600 dark:text-slate-400">@lang('state.coming_soon')</p>
                @else
                    <ul class="mt-4 divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach ($documents as $document)
                            @if ($document->isAvailableIn($locale->value))
                                <li class="flex items-center justify-between gap-4 py-3">
                                    <span class="font-medium text-slate-900 dark:text-white">{{ $document->title }}</span>
                                    <a href="{{ $document->downloadUrl() }}"
                                       class="text-sm font-medium text-brand hover:underline">
                                        @lang('action.download')
                                        @if ($document->formattedSize())
                                            <span class="font-mono text-xs text-slate-400">({{ $document->formattedSize() }})</span>
                                        @endif
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif
    </div>
@endsection
