{{--
    The programme, by day.

    Day tabs as real links that the same component serves two different
    purposes with: `route-name` is `programme` on the programme page and `home`
    on the landing page, so switching a day on the landing page stays on the
    landing page. It used to be hard-coded to `route('programme')`, which meant
    the landing page's own day tabs navigated away to the programme page.

    The selected day is in the query string, so a day is a bookmarkable,
    shareable URL and the back button behaves — a JS tab that keeps that state
    in a variable makes two different days one URL, unshareable and invisible to
    a crawler. The `data-day-tab` script below then upgrades those links to
    client-side tabs: every day's timetable is already in the document, so
    switching is instant instead of a full page load, and if the script never
    runs the links still work because they are real `href`s.

    One row per start time, not one row per session. Two sessions that begin
    together are one moment in a timetable, and splitting them invents a second
    time column that a delegate then has to reconcile against the first.

    The "+" on a row opens the session's detail — speakers, room, format. It is
    a real button with `aria-expanded` and `aria-controls`, and the panel it
    opens is an ordinary part of the document, so it stays readable with
    scripting off even though it starts collapsed.

    The decorative shapes the 2024 build floated over this text are gone rather
    than dimmed: anything that overlaps copy is one viewport-width change away
    from overlapping it again, and opacity does not remove the pointer events.
--}}
@props([
    'days',
    'slots',
    'selectedDay',
    'locale',
    'programmeDocument' => null,
    // The language-specific file shipped in `public/`, resolved by
    // Edition::programmePdfUrl(). It is the middle rung of the download
    // fallback below: a published document still wins over a committed file.
    'pdfUrl' => null,
    // Overrides the band heading. The landing page wants "Conference
    // programme"; the programme page, whose hero already carries the title,
    // can pass something quieter.
    'title' => null,
    // Which route the day tabs link back to. `programme` on the programme page,
    // `home` on the landing page: the tabs have to return to the page they are
    // on, not to whichever page this component was copied from.
    'routeName' => 'programme',
])

@if (count($days) > 0 && count($slots) > 0)
    @php
        $byDay = [];

        foreach ($slots as $date => $times) {
            foreach ($times as $start => $sessions) {
                $byDay[$date][] = ['start' => $start, 'sessions' => $sessions];
            }
        }

        $activeKey = $selectedDay?->toDateString() ?? array_key_first($byDay);
        $activeIndex = array_search($activeKey, array_keys($byDay), true);
        $activeIndex = $activeIndex === false ? 0 : $activeIndex;

        $dayKeys = array_keys($byDay);

        // Three rungs, in order of freshness: a published document wins, so
        // publishing a revision updates the button instead of leaving the file
        // committed here on the page; then the language-specific programme PDF
        // shipped in `public/` (French for AR/FR, English for EN, each falling
        // back to `programme.pdf`); then the programme page itself, because a
        // bare link to the programme page is still not a download. Never a
        // link to a file that is not there.
        $downloadUrl = $programmeDocument?->downloadUrl()
            ?? $pdfUrl
            ?? route('programme', array_filter(['locale' => request()->route('locale')]));
    @endphp

    <section class="h-programme" aria-labelledby="programme-title">

        {{-- The pattern unit, clipped against both edges of the band. --}}
        <span class="h-programme__edge h-programme__edge--start" aria-hidden="true"></span>
        <span class="h-programme__edge h-programme__edge--end" aria-hidden="true"></span>

        <div class="container">

            <h2 id="programme-title" class="h-programme__title">
                {{ $title ?? __('home.landing.programme.title') }}
            </h2>

            <div class="h-programme__grid">

                {{-- A real tablist. The links are the tabs, not a `button` with a
                     click handler pretending: `href` means the day is a URL, the
                     tab roles mean arrow-key navigation is the browser's, and the
                     two work together rather than against each other. --}}
                <ul class="h-days" role="tablist"
                    data-day-tabs
                    aria-label="@lang('home.landing.programme.days_label')">
                    @foreach ($dayKeys as $index => $date)
                        @php
                            $day = \Illuminate\Support\Carbon::parse($date);
                            $dayUrl = route($routeName, array_filter([
                                'locale' => request()->route('locale'),
                                'day' => $day->toDateString(),
                            ]));
                            $isCurrent = $date === $activeKey;
                            $panelId = 'programme-day-'.$index;
                        @endphp
                        <li role="presentation">
                            <a href="{{ $dayUrl }}"
                               id="programme-tab-{{ $index }}"
                               class="h-day{{ $isCurrent ? ' h-day--current' : '' }}"
                               role="tab"
                               data-day-tab="{{ $panelId }}"
                               aria-controls="{{ $panelId }}"
                               aria-selected="{{ $isCurrent ? 'true' : 'false' }}"
                               tabindex="{{ $isCurrent ? '0' : '-1' }}"
                               @if ($isCurrent) aria-current="true" @endif>
                                <span class="h-day__label">
                                    @lang('home.landing.programme.day', ['number' => $index + 1])
                                </span>
                                <span class="h-day__num" dir="ltr">{{ $day->format('j') }}</span>
                                <span class="h-day__month">{{ $day->translatedFormat('F Y') }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="h-schedule">

                    {{-- Every day's timetable is in the document and the inactive
                         ones carry `hidden`. That is what makes the tabs instant
                         with scripting, and it is also what keeps them working
                         without it: the server picks the day from `?day=` and
                         renders it open, and the links are the fallback rather
                         than the enhancement being the only route.

                         The panels are per-day rather than one shared panel
                         because each day carries its own heading — a screen
                         reader has to be able to announce which day it moved
                         to, not just that something changed. --}}
                    @foreach ($dayKeys as $index => $date)
                        @php
                            $isCurrent = $date === $activeKey;
                            $day = \Illuminate\Support\Carbon::parse($date);
                        @endphp

                        <div class="h-schedule__panel"
                             id="programme-day-{{ $index }}"
                             role="tabpanel"
                             aria-labelledby="programme-tab-{{ $index }}"
                             tabindex="0"
                             @unless ($isCurrent) hidden @endunless>

                            <header class="h-schedule__head">
                                <span class="h-schedule__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3.5" y="5" width="17" height="15.5" rx="2.5"></rect>
                                        <path d="M8 3v4M16 3v4M3.5 10h17M8 14h3M8 17h6"></path>
                                    </svg>
                                </span>
                                <div>
                                    <p class="h-schedule__day">
                                        @lang('home.landing.programme.day', ['number' => $index + 1])
                                    </p>
                                    <p class="h-schedule__date">
                                        {{ $day->translatedFormat('l j F Y') }}
                                    </p>
                                </div>
                            </header>

                            <ol class="h-slots">
                                @foreach ($byDay[$date] as $slotIndex => $slot)
                                    @php
                                        // A time can carry several sessions; the first is
                                        // the headline and the rest are listed under it,
                                        // which is how a programme without nested times
                                        // actually reads.
                                        // $slot['sessions'] may be a Collection or an array.
                                        $sessions = collect($slot['sessions'])->values();
                                        $lead = $sessions->first();
                                        $extra = $sessions->slice(1);

                                        // Day *and* slot, because every day's panel is now in
                                        // the document at once: `aria-controls` has to be a
                                        // unique id on the page, not a unique id within
                                        // whichever day happens to be open.
                                        $panelId = 'slot-'.$index.'-'.$slotIndex;
                                    @endphp

                                    @if ($lead)
                                        <li class="h-slot">
                                            <span class="h-slot__time" dir="ltr">{{ $slot['start'] }}</span>

                                            <div class="h-slot__body">
                                                <span class="h-slot__format">{{ $lead->format->label($locale->value) }}</span>
                                                <h3 class="h-slot__title">{{ $lead->title }}</h3>

                                                @if ($lead->speakers->isNotEmpty())
                                                    <p class="h-slot__note">
                                                        {{ $lead->speakers->map(fn ($speaker) => $speaker->fullName())->join(', ') }}
                                                    </p>
                                                @endif

                                                <div class="h-slot__detail" id="{{ $panelId }}" hidden>
                                                    <p class="h-slot__detail-line" dir="ltr">
                                                        <i class="far fa-clock" aria-hidden="true"></i>
                                                        {{ $lead->startsAtString() }}–{{ $lead->endsAtString() }}
                                                    </p>

                                                    @if ($lead->room)
                                                        <p class="h-slot__detail-line">
                                                            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                                            {{ $lead->room->name }}
                                                        </p>
                                                    @endif

                                                    @if ($lead->track)
                                                        <p class="h-slot__detail-line">
                                                            <i class="fas fa-layer-group" aria-hidden="true"></i>
                                                            {{ $lead->track->name }}
                                                        </p>
                                                    @endif

                                                    @foreach ($extra as $session)
                                                        <p class="h-slot__detail-line">{{ $session->title }}</p>
                                                    @endforeach
                                                </div>
                                            </div>

                                            {{-- Only where there is something to open. A
                                                 toggle that reveals an empty panel is a
                                                 control that lies about the page. --}}
                                            @if ($lead->room || $lead->track || $lead->speakers->isNotEmpty() || $extra->isNotEmpty())
                                                <button type="button"
                                                        class="h-slot__toggle"
                                                        data-slot-toggle
                                                        data-label-close="{{ __('home.landing.programme.collapse') }}"
                                                        aria-expanded="false"
                                                        aria-controls="{{ $panelId }}">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                                                        <path d="M12 6v12M6 12h12"></path>
                                                    </svg>
                                                    <span class="visually-hidden">@lang('home.landing.programme.expand')</span>
                                                </button>
                                            @endif
                                        </li>
                                    @endif
                                @endforeach
                            </ol>

                        </div>
                    @endforeach

                </div>

            </div>

            {{-- Two ways onward, side by side, on the landing page. The download
                 is the document the delegate already has; "See more" is the full
                 programme, because what is in that band is a *sample* — a handful
                 of sessions per day — and the button is what tells the truth
                 about that. On the programme page the band is the full
                 programme, so "See more" is dropped and only the download
                 remains. --}}
            <div class="h-programme__actions">
                {{-- On the programme page the band *is* the programme, so a
                     "See more" that returns here would be a control with no
                     destination. The download then stands alone as the primary
                     act instead of the secondary one. --}}
                @if ($routeName !== 'programme')
                    <a href="{{ route('programme', array_filter(['locale' => request()->route('locale')])) }}" class="h-btn h-btn--fill-light">
                        <span>@lang('home.landing.programme.more')</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h13M13 6.5 18.5 12 13 17.5"></path>
                        </svg>
                    </a>
                @endif

                <a href="{{ $downloadUrl }}" class="h-btn {{ $routeName === 'programme' ? 'h-btn--fill-light' : 'h-btn--outline-light' }}">
                    <span>@lang('home.landing.programme.download')</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 3.5v11M7.5 10.5 12 15l4.5-4.5M4.5 19.5h15"></path>
                    </svg>
                </a>
            </div>

        </div>
    </section>
@endif
<style>
    /* Arabic: the time column is on the right, so align the time to its right edge,
   which leaves the gap before the dot that the French layout has. */
    [dir="rtl"] .h-slot__time {
        text-align: right;
    }
</style>