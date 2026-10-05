{{--
    The programme, by day.

    Day tabs as real links rather than JS tabs. The selected day is in the
    query string, so a day is a bookmarkable, shareable URL and the back button
    behaves — a JS tab keeps that state in a variable, which makes two
    different days one URL, unshareable and invisible to a crawler. It also
    means the day column works with scripting disabled.

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
@props(['days', 'slots', 'selectedDay', 'locale', 'programmeDocument' => null])

@if ($days->isNotEmpty() && $slots->isNotEmpty())
    @php
        $byDay = [];

        foreach ($slots as $date => $times) {
            foreach ($times as $start => $sessions) {
                $byDay[$date][] = ['start' => $start, 'sessions' => $sessions];
            }
        }

        $activeKey = $selectedDay?->toDateString() ?? array_key_first($byDay);
        $visible = $byDay[$activeKey] ?? reset($byDay);
        $activeIndex = array_search($activeKey, array_keys($byDay), true);
        $activeIndex = $activeIndex === false ? 0 : $activeIndex;

        // A published PDF when there is one, the programme page when there is
        // not. Never a link to a file that is not there.
        $downloadUrl = $programmeDocument?->downloadUrl() ?? route('programme');
    @endphp

    <section class="h-programme" aria-labelledby="programme-title">

        {{-- The pattern unit, clipped against both edges of the band. --}}
        <span class="h-programme__edge h-programme__edge--start" aria-hidden="true"></span>
        <span class="h-programme__edge h-programme__edge--end" aria-hidden="true"></span>

        <div class="container">

            <h2 id="programme-title" class="h-programme__title">
                @lang('home.landing.programme.title')
            </h2>

            <div class="h-programme__grid">

                <ul class="h-days">
                    @foreach (array_keys($byDay) as $index => $date)
                        @php
                            $day = \Illuminate\Support\Carbon::parse($date);
                            $dayUrl = route('programme', array_filter([
                                'locale' => request()->route('locale'),
                                'day' => $day->toDateString(),
                            ]));
                            $isCurrent = $date === $activeKey;
                        @endphp
                        <li>
                            <a href="{{ $dayUrl }}"
                               @class(['h-day', 'h-day--current' => $isCurrent])
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

                    <header class="h-schedule__head">
                        <span class="h-schedule__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3.5" y="5" width="17" height="15.5" rx="2.5"></rect>
                                <path d="M8 3v4M16 3v4M3.5 10h17M8 14h3M8 17h6"></path>
                            </svg>
                        </span>
                        <div>
                            <p class="h-schedule__day">
                                @lang('home.landing.programme.day', ['number' => $activeIndex + 1])
                            </p>
                            <p class="h-schedule__date">
                                {{ \Illuminate\Support\Carbon::parse($activeKey)->translatedFormat('l j F Y') }}
                            </p>
                        </div>
                    </header>

                    <ol class="h-slots">
                        @foreach ($visible as $slotIndex => $slot)
                            @php
                                // A time can carry several sessions; the first is
                                // the headline and the rest are listed under it,
                                // which is how a programme without nested times
                                // actually reads.
                                // $slot['sessions'] may be a Collection or an array.
                                $sessions = collect($slot['sessions'])->values();
                                $lead = $sessions->first();
                                $extra = $sessions->slice(1);
                                $panelId = 'slot-'.$activeIndex.'-'.$slotIndex;
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

            </div>

            <p class="h-programme__download">
                {{-- `--outline-light`, not `--outline`: this control sits on the dark
                     programme band, where the brand-mauve outline is about 1.3:1
                     against the background and effectively invisible. --}}
                <a href="{{ $downloadUrl }}" class="h-btn h-btn--outline-light">
                    <span>@lang('home.landing.programme.download')</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 3.5v11M7.5 10.5 12 15l4.5-4.5M4.5 19.5h15"></path>
                    </svg>
                </a>
            </p>

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