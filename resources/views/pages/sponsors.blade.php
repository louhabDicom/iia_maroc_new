@extends('layouts.app')

@section('title', __('sponsoring.title'))

@section('content')
    <div class="mx-auto max-w-5xl">

        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
            @lang('sponsoring.title')
        </h1>

        <div class="mt-10 space-y-12">
            {{-- Every tier in the catalogue gets a section, including the empty
                 ones. A gold section with nothing in it reads as "gold
                 sponsorship is still available", which is the reason for showing
                 it; hiding the tier would just make the offer invisible. --}}
            @foreach ($tierOrder as $tier => $rank)
                @php
                    $inTier = $tiers[$tier] ?? collect();
                    $content = $tierContent->get($tier);
                @endphp

                <section aria-labelledby="tier-{{ $tier }}">
                    <h2 id="tier-{{ $tier }}" class="text-xl font-semibold text-slate-900 dark:text-white">
                        {{ __("sponsoring.tier.{$tier}") }}
                    </h2>

                    @if ($content?->text())
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $content->text() }}</p>
                    @endif

                    @if ($inTier->isEmpty())
                        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                            @lang('sponsoring.no_sponsors')
                        </p>
                    @else
                        {{-- Logos: monochrome below the top tier so a row of
                             colourful logos does not compete with the tier
                             heading, which is the actual hierarchy. --}}
                        <ul class="mt-5 flex flex-wrap items-center gap-8">
                            @foreach ($inTier as $sponsor)
                                <li>
                                    @if ($sponsor->website_url)
                                        <a href="{{ $sponsor->website_url }}"
                                           rel="noopener noreferrer sponsored"
                                           target="_blank">
                                            <x-sponsor-logo :sponsor="$sponsor" />
                                        </a>
                                    @else
                                        <x-sponsor-logo :sponsor="$sponsor" />
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach
        </div>

        @if ($organisations->isNotEmpty())
            <section class="mt-16 border-t border-slate-200 pt-10 dark:border-slate-800">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">@lang('home.sponsors_title')</h2>
                <ul class="mt-5 space-y-4">
                    @foreach ($organisations as $organisation)
                        <li>
                            <p class="font-medium text-slate-900 dark:text-white">{{ $organisation->name }}</p>
                            @if ($organisation->role)
                                <p class="text-sm text-slate-600 dark:text-slate-400">{{ $organisation->role }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($previousSponsors->isNotEmpty())
            <section class="mt-16 border-t border-slate-200 pt-10 dark:border-slate-800">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Sponsors des éditions précédentes</h2>
                <ul class="mt-5 flex flex-wrap items-center gap-8">
                    @foreach ($previousSponsors as $sponsor)
                        <li>
                            @if ($sponsor->website_url)
                                <a href="{{ $sponsor->website_url }}"
                                   rel="noopener noreferrer sponsored"
                                   target="_blank">
                                    <x-sponsor-logo :sponsor="$sponsor" />
                                </a>
                            @else
                                <x-sponsor-logo :sponsor="$sponsor" />
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <p class="mt-16">
            <a href="{{ route('contact') }}" class="btn btn-primary">@lang('sponsoring.become_partner')</a>
        </p>
    </div>
@endsection
