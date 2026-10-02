{{--
    The line-up, as a horizontal rail rather than a grid.

    A grid of eighteen cards turns the page into a directory. The reference
    design shows four and says "see them all", which is the honest shape for a
    line-up that is still being announced: the rail scrolls, the arrows move it
    by one card-width, and the "see all" link is the real answer for anyone who
    wants the directory.

    The rail is a scroll container with scroll-snap, so it is usable by touch,
    by trackpad, by keyboard and by the arrow buttons without any of them being
    the only way. The buttons are progressive enhancement: they stay hidden
    unless the rail actually overflows, which design.js decides, so there is
    never a pair of arrows sitting there with nothing to scroll.

    Every card falls back to initials on a brand gradient. That is a real state
    right now — speakers on file and no photographs — and a grid of grey
    silhouettes reads as a bug, whereas initials name the person.
--}}
@props(['speakers'])

@if ($speakers->isNotEmpty())
    <section class="h-lineup" aria-labelledby="lineup-title">
        <div class="container">

            <div class="h-lineup__head">
                <div>
                    <p class="h-lineup__eyebrow">@lang('home.landing.speakers.eyebrow')</p>
                    <h2 id="lineup-title" class="h-lineup__title">@lang('home.speakers_title')</h2>
                </div>

                <a href="{{ route('speakers') }}" class="h-lineup__all">
                    @lang('home.speakers_more')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 12h15M13 6l6 6-6 6"></path>
                    </svg>
                </a>
            </div>

            <div class="h-lineup__rail-wrap">
                <button type="button" class="h-lineup__arrow h-lineup__arrow--prev"
                        data-rail-prev
                        data-rail="lineup"
                        hidden>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 5l-7 7 7 7"></path>
                    </svg>
                    <span class="visually-hidden">@lang('home.landing.speakers.previous')</span>
                </button>

                <ul class="h-lineup__rail" data-rail-track="lineup">
                    @foreach ($speakers as $speaker)
                        @php
                            $photo = $speaker->photo_path
                                ? (\Illuminate\Support\Str::startsWith($speaker->photo_path, 'http')
                                    ? $speaker->photo_path
                                    : asset('storage/'.$speaker->photo_path))
                                : null;
                        @endphp

                        <li>
                            <article class="h-person">
                                <span class="h-person__avatar">
                                    @if ($photo)
                                        <img src="{{ $photo }}"
                                             alt="{{ $speaker->fullName() }}"
                                             width="120"
                                             height="120"
                                             loading="lazy"
                                             decoding="async">
                                    @else
                                        <span class="h-person__initials" aria-hidden="true">
                                            {{ $speaker->initials() }}
                                        </span>
                                    @endif
                                </span>

                                <div class="h-person__body">
                                    <h3 class="h-person__name">{{ $speaker->fullName() }}</h3>

                                    @if ($speaker->job_title)
                                        <p class="h-person__role">{{ $speaker->job_title }}</p>
                                    @endif

                                    @if ($speaker->organisation)
                                        <p class="h-person__org">{{ $speaker->organisation }}</p>
                                    @endif
                                </div>

                                {{-- The chip is the speaker's role in the
                                     programme, read from the row rather than
                                     invented per card: `is_keynote` is a fact
                                     somebody set, and the alternative is eight
                                     identical labels that say nothing. --}}
                                <p class="h-person__chip">
                                    <i class="{{ $speaker->is_keynote ? 'fas fa-star' : 'fas fa-microphone-lines' }}"
                                       aria-hidden="true"></i>
                                    @lang($speaker->is_keynote ? 'home.landing.speakers.keynote' : 'home.landing.speakers.speaker')
                                </p>
                            </article>
                        </li>
                    @endforeach
                </ul>
<button type="button" class="h-lineup__arrow h-lineup__arrow--next"
                        data-rail-next
                        data-rail="lineup"
                        hidden>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 5l7 7-7 7"></path>
                    </svg>
                    <span class="visually-hidden">@lang('home.landing.speakers.next')</span>
                </button>
            </div>

        </div>
    </section>
@endif