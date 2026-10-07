{{--
    The programme, by day: the full ARABCIA 2026 timetable.

    What changed compared with the DB-driven version
    ------------------------------------------------
    * The timetable is now the real two-day programme (Programme détaillé),
      read from the same `programme.page.*` lang keys as the programme page,
      so FR / EN / AR all work and there is one place to edit the text.
      `days`, `slots`, `selectedDay` and `locale` are still accepted so
      existing callers do not break, but `slots` is no longer used.
    * Fixed-height scroll area: the section keeps the same height whatever the
      day, and the timetable scrolls inside it (see `--hp-scroll` below).
    * Same markup contract as before for the day tabs (`data-day-tab`,
      `role="tab"`, real `href`s, `?day=` in the query string), so the existing
      day-tab script keeps working untouched.
    * The "Ateliers — Trois parcours" row is a tab group: one tab per parcours,
      each with a timeline of its three sessions.
--}}
@props([
    'days' => [],
    'slots' => [],
    'selectedDay' => null,
    'locale' => null,
    'programmeDocument' => null,
    'pdfUrl' => null,
    'title' => null,
    'routeName' => 'programme',
])

@php
    // The two conference days.
    $dayKeys = ['2026-12-16', '2026-12-17'];

    $requested = $selectedDay?->toDateString() ?? request()->query('day');
    $activeKey = in_array($requested, $dayKeys, true) ? $requested : $dayKeys[0];

    // [start, end, type, lang key under programme.page.s.*]
    $schedule = [
        1 => [
            ['08:00', '09:00', 'welcome',   'welcome1'],
            ['09:30', '10:00', 'ceremony',  'ceremony'],
            ['10:00', '10:30', 'trophy',    'trophies'],
            ['10:30', '11:15', 'plenary',   'pl1'],
            ['11:15', '11:45', 'break',     'break'],
            ['11:45', '12:30', 'plenary',   'pl2'],
            ['12:30', '13:30', 'plenary',   'pl3'],
            ['13:30', '14:30', 'lunch',     'lunch'],
            ['14:30', '16:20', 'workshops', 'workshops'],
            ['16:20', '16:50', 'break',     'break'],
            ['16:50', '17:35', 'lab',       'lab1'],
        ],
        2 => [
            ['08:00', '09:00', 'welcome',   'welcome2'],
            ['09:00', '10:00', 'plenary',   'pl4'],
            ['10:00', '11:00', 'plenary',   'pl5'],
            ['11:00', '11:30', 'break',     'break'],
            ['11:30', '12:30', 'plenary',   'pl6'],
            ['12:30', '13:30', 'plenary',   'pl7'],
            ['13:30', '14:30', 'lunch',     'lunch'],
            ['14:30', '16:20', 'workshops', 'workshops'],
            ['16:20', '16:50', 'break',     'break'],
            ['16:50', '17:35', 'lab',       'lab2'],
        ],
    ];

    // Row icon + bubble tone by type.
    $types = [
        'welcome'   => ['fa-users',              'soft'],
        'ceremony'  => ['fa-landmark',           'main'],
        'trophy'    => ['fa-trophy',             'main'],
        'plenary'   => ['fa-microphone-alt',     'main'],
        'break'     => ['fa-coffee',             'soft'],
        'lunch'     => ['fa-utensils',           'soft'],
        'workshops' => ['fa-chalkboard-teacher', 'accent'],
        'lab'       => ['fa-lightbulb',          'accent'],
    ];

    // The three workshop parcours.
    $tracks = [
        ['tone' => 'ai',  'icon' => 'fa-brain',         'title' => 'programme.page.track_ai_title',         'text' => 'programme.page.track_ai'],
        ['tone' => 'res', 'icon' => 'fa-shield-alt',    'title' => 'programme.page.track_resilience_title', 'text' => 'programme.page.track_res'],
        ['tone' => 'aud', 'icon' => 'fa-user-graduate', 'title' => 'programme.page.track_auditor_title',    'text' => 'programme.page.track_aud'],
    ];

    $slotTimes = ['14:30 – 15:00', '15:10 – 15:40', '15:50 – 16:20'];

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

            {{-- Day tabs: real links, upgraded to client-side tabs by the existing script. --}}
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

                @foreach ($dayKeys as $index => $date)
                    @php
                        $n = $index + 1;
                        $isCurrent = $date === $activeKey;
                        $day = \Illuminate\Support\Carbon::parse($date);
                    @endphp

                    <div class="h-schedule__panel"
                         id="programme-day-{{ $index }}"
                         role="tabpanel"
                         aria-labelledby="programme-tab-{{ $index }}"
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
                                    @lang('home.landing.programme.day', ['number' => $n])
                                </p>
                                <p class="h-schedule__date">
                                    {{ $day->translatedFormat('l j F Y') }}
                                </p>
                            </div>
                        </header>

                        {{-- Fixed-height scroll area: the section never grows. --}}
                        <div class="h-slots-wrap" data-slots-wrap>
                            <div class="h-slots-scroll"
                                 data-slots-scroll
                                 role="region"
                                 tabindex="0"
                                 aria-label="@lang('home.landing.programme.day', ['number' => $n]) — {{ $day->translatedFormat('l j F Y') }}">

                                <ol class="h-slots">
                                    @foreach ($schedule[$n] as $r => [$start, $end, $type, $key])
                                        @php
                                            $t = __('programme.page.s.'.$key);
                                            [$icon, $tone] = $types[$type];
                                        @endphp

                                        <li class="h-slot h-slot--{{ $type }}" style="--i: {{ $r }}">
                                            <span class="h-slot__time" dir="ltr">
                                                <span class="h-slot__start">{{ $start }}</span>
                                                <span class="h-slot__end">→ {{ $end }}</span>
                                            </span>

                                            <div class="h-slot__body">
                                                <div class="h-slot__head">
                                                    <span class="h-slot__ico h-slot__ico--{{ $tone }}" aria-hidden="true">
                                                        <i class="fas {{ $icon }}"></i>
                                                    </span>
                                                    <h3 class="h-slot__title">{{ $t['title'] }}</h3>
                                                </div>

                                                @isset($t['subtitle'])
                                                    <p class="h-slot__sub">{{ $t['subtitle'] }}</p>
                                                @endisset

                                                @isset($t['desc'])
                                                    <p class="h-slot__note">{{ $t['desc'] }}</p>
                                                @endisset

                                                @if (! empty($t['bullets']))
                                                    <ul class="h-slot__chips">
                                                        @foreach ($t['bullets'] as $bullet)
                                                            <li>{{ $bullet }}</li>
                                                        @endforeach
                                                    </ul>
                                                @endif

                                                @if ($type === 'workshops')
                                                    @php
                                                        $sessions = __('programme.page.ws.d'.$n);
                                                        $wid = 'h-ws-'.$n;
                                                    @endphp

                                                    <div class="h-ws" data-ws-tabs>
                                                        <div class="h-ws__list" role="tablist" aria-label="{{ $t['title'] }}">
                                                            @foreach ($tracks as $track)
                                                                <button type="button"
                                                                        role="tab"
                                                                        id="{{ $wid }}-tab-{{ $track['tone'] }}"
                                                                        aria-controls="{{ $wid }}-panel-{{ $track['tone'] }}"
                                                                        aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                                                        tabindex="{{ $loop->first ? '0' : '-1' }}"
                                                                        class="h-ws__tab h-ws__tab--{{ $track['tone'] }}">
                                                                    <span class="h-ws__ico" aria-hidden="true"><i class="fas {{ $track['icon'] }}"></i></span>
                                                                    <span class="h-ws__label">{{ __($track['title']) }}</span>
                                                                </button>
                                                            @endforeach
                                                        </div>

                                                        @foreach ($tracks as $track)
                                                            <div role="tabpanel"
                                                                 id="{{ $wid }}-panel-{{ $track['tone'] }}"
                                                                 aria-labelledby="{{ $wid }}-tab-{{ $track['tone'] }}"
                                                                 tabindex="0"
                                                                 class="h-ws__panel h-ws__panel--{{ $track['tone'] }}"
                                                                 @unless ($loop->first) hidden @endunless>
                                                                <p class="h-ws__lead">{{ __($track['text']) }}</p>

                                                                <ol class="h-ws__timeline">
                                                                    @foreach ($sessions as $i => $session)
                                                                        <li class="h-ws__item">
                                                                            <span class="h-ws__time" dir="ltr">
                                                                                <i class="far fa-clock" aria-hidden="true"></i>{{ $slotTimes[$i] }}
                                                                            </span>
                                                                            <p class="h-ws__title">{{ $session[$track['tone']] }}</p>
                                                                        </li>
                                                                    @endforeach
                                                                </ol>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>

                            <span class="h-scroll-hint" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 9.5l6 6 6-6"></path>
                                </svg>
                            </span>
                        </div>

                    </div>
                @endforeach

            </div>

        </div>

        <div class="h-programme__actions">
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

<style>
    /* Arabic: the time column is on the right, so align the time to its right edge,
       which leaves the gap before the dot that the French layout has. */
    [dir="rtl"] .h-slot__time { text-align: right; }

    .h-programme {
        /* >>> The height of the timetable area. Change this one value to match
               the old section height; the section never grows past it. <<< */
        --hp-scroll: clamp(380px, 64vh, 540px);

        --hp-ink: #1b1464;
        --hp-deep: #2b1d9a;
        --hp-brand: #3a27b3;
        --hp-muted: #5f6384;
        --hp-line: #e4e1f4;
        --hp-tint: #eeebfb;
        --hp-surface: #fff;

        /* chosen day tab: change these two to restyle it */
        --hp-day-active-bg: #fff;
        --hp-day-active-ink: #2b1d9a;
    }

    /* ---------- Day tabs: the chosen one ----------
       Keyed on aria-selected, which the tab script keeps correct, instead of
       relying only on .h-day--current (which is set on page load and was
       never moved when switching day). */
    .h-programme .h-day {
        transition: background-color .25s, color .25s, box-shadow .25s, transform .25s, border-color .25s;
    }

.h-programme .h-day[aria-selected="true"] {
    background: var(--hp-day-active-ink);
    border-color: var(--hp-day-active-bg);
    color: wheat !important;
    box-shadow: 0 18px 30px -16px rgba(0, 0, 0, .55);
}

.h-programme .h-day[aria-selected="true"] .h-day__label,
.h-programme .h-day[aria-selected="true"] .h-day__num,
.h-programme .h-day[aria-selected="true"] .h-day__month {
    color: wheat !important;
    opacity: 1;
}

    /* ---------- Scroll area ---------- */
    .h-slots-wrap { position: relative; }

    .h-slots-scroll {
        height: var(--hp-scroll);
        overflow-y: auto;
        overscroll-behavior: contain;
        scroll-behavior: smooth;
        scrollbar-width: thin;
        scrollbar-color: #c4bdee transparent;
        padding-inline-end: 6px;
    }

    .h-slots-scroll::-webkit-scrollbar { width: 6px; }
    .h-slots-scroll::-webkit-scrollbar-track { background: transparent; }
    .h-slots-scroll::-webkit-scrollbar-thumb { background: #c4bdee; border-radius: 99px; }
    .h-slots-scroll::-webkit-scrollbar-thumb:hover { background: var(--hp-brand); }

    .h-slots-scroll:focus-visible {
        outline: 2px solid var(--hp-brand);
        outline-offset: -2px;
        border-radius: 10px;
    }

    .h-programme .h-slots { margin: 0; }

    /* soft edges that tell the visitor there is more above / below */
    .h-slots-wrap::before,
    .h-slots-wrap::after {
        content: "";
        position: absolute;
        inset-inline: 0;
        height: 56px;
        z-index: 1;
        pointer-events: none;
        transition: opacity .25s;
    }

    .h-slots-wrap::before {
        top: 0;
        opacity: 0;
        background: linear-gradient(to bottom, var(--hp-surface) 10%, rgba(255, 255, 255, 0));
    }

    .h-slots-wrap::after {
        bottom: 0;
        background: linear-gradient(to top, var(--hp-surface) 12%, rgba(255, 255, 255, 0));
    }

    .h-slots-wrap[data-scrolled="true"]::before { opacity: 1; }
    .h-slots-wrap[data-end="true"]::after { opacity: 0; }

    .h-scroll-hint {
        position: absolute;
        left: 50%;
        bottom: 12px;
        z-index: 2;
        display: grid;
        place-items: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(150deg, var(--hp-deep), var(--hp-brand));
        color: #fff;
        box-shadow: 0 12px 22px -10px rgba(43, 29, 154, .85);
        transform: translateX(-50%);
        pointer-events: none;
        animation: hpBounce 1.8s ease-in-out infinite;
        transition: opacity .25s, visibility .25s;
    }

    .h-scroll-hint svg { width: 18px; height: 18px; }

    .h-slots-wrap[data-scrolled="true"] .h-scroll-hint,
    .h-slots-wrap[data-end="true"] .h-scroll-hint { opacity: 0; visibility: hidden; }

    @keyframes hpBounce {
        0%, 100% { transform: translate(-50%, 0); }
        50%      { transform: translate(-50%, 5px); }
    }

    /* ---------- Rows ---------- */
    .h-schedule__panel:not([hidden]) .h-slot {
        animation: hpRowIn .5s cubic-bezier(.2, .7, .2, 1) both;
        animation-delay: calc(min(var(--i, 0), 9) * 45ms);
    }

    @keyframes hpRowIn {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: none; }
    }

    .h-slot__time { line-height: 1.25; }
    .h-slot__start { display: block; font-weight: 700; }
    .h-slot__end   { display: block; font-size: .74em; font-weight: 500; opacity: .65; white-space: nowrap; }

    .h-slot__head { display: flex; align-items: center; gap: 10px; }

    .h-slot__ico {
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        font-size: .78rem;
    }

    .h-slot__ico--main {
        background: linear-gradient(150deg, #2b1d9a, #3a27b3);
        color: #fff;
        box-shadow: 0 10px 18px -10px rgba(43, 29, 154, .8);
    }

    .h-slot__ico--accent {
        background: linear-gradient(150deg, #7a5af8, #5a3fd0);
        color: #fff;
        box-shadow: 0 10px 18px -10px rgba(90, 63, 208, .8);
    }

    .h-slot__ico--soft { background: var(--hp-tint); color: var(--hp-brand); }

    .h-slot__sub {
        margin: 8px 0 0;
        font-size: .9rem;
        font-weight: 600;
        line-height: 1.5;
        color: var(--hp-ink);
    }

    .h-programme .h-slot__note { margin-block-start: 4px; line-height: 1.6; }

    /* breaks and meals are quieter than sessions */
    .h-slot--break .h-slot__title,
    .h-slot--lunch .h-slot__title,
    .h-slot--welcome .h-slot__title { font-weight: 600; opacity: .8; }

    .h-slot--break .h-slot__ico,
    .h-slot--lunch .h-slot__ico { width: 26px; height: 26px; font-size: .7rem; }

    .h-slot__chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin: 10px 0 0;
        padding: 0;
        list-style: none;
    }

    .h-slot__chips li {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 11px;
        border: 1px solid #ddd5fb;
        border-radius: 999px;
        background: var(--hp-tint);
        font-size: .76rem;
        font-weight: 600;
        color: var(--hp-brand);
    }

    .h-slot__chips li::before {
        content: "";
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: #7a5af8;
    }

    /* ---------- Workshop tabs ---------- */
    .h-ws { margin-block-start: 14px; }

    .h-ws__tab--ai,  .h-ws__panel--ai  { --tone: #5a3fd0; --tone-bg: #efeaff; --tone-line: #ddd3fb; --tone-grad: linear-gradient(140deg, #6b4fe3, #4a31c4); }
    .h-ws__tab--res, .h-ws__panel--res { --tone: #12857f; --tone-bg: #e2f6f4; --tone-line: #c4ebe7; --tone-grad: linear-gradient(140deg, #1aa59d, #0e6f6a); }
    .h-ws__tab--aud, .h-ws__panel--aud { --tone: #2a8a4b; --tone-bg: #e6f6ea; --tone-line: #cbebd3; --tone-grad: linear-gradient(140deg, #38a85f, #1f7340); }

    .h-ws__list {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
    }

    .h-ws__tab {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        padding: 12px 6px 11px;
        border: 1px solid var(--hp-line);
        border-radius: 10px;
        background: #fff;
        color: var(--hp-muted);
        font: inherit;
        font-size: .72rem;
        font-weight: 700;
        line-height: 1.3;
        text-align: center;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
        transition: transform .2s, box-shadow .2s, background-color .2s, color .2s, border-color .2s;
    }

    .h-ws__tab:hover {
        transform: translateY(-2px);
        border-color: var(--tone);
        background: var(--tone-bg);
        color: var(--tone);
    }
    .h-ws__tab:focus-visible { outline: 2px solid var(--tone); outline-offset: 2px; }

    .h-ws__ico {
        display: grid;
        place-items: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--tone-bg);
        color: var(--tone);
        font-size: .95rem;
        transition: background-color .2s, color .2s, transform .25s;
    }

    /* chosen tab: solid colour fill + soft ring, clearly different from the white ones */
    .h-ws__tab[aria-selected="true"],
    .h-ws__tab[aria-selected="true"]:hover {
        border-color: var(--tone);
        background: var(--tone-grad);
        color: #fff;
        box-shadow: 0 0 0 3px var(--tone-bg), 0 14px 22px -12px var(--tone);
        transform: none;
    }

    .h-ws__tab[aria-selected="true"] .h-ws__ico {
        background: rgba(255, 255, 255, .22);
        color: #fff;
        transform: scale(1.08);
    }

    .h-ws__tab[aria-selected="true"]::after {
        content: "";
        position: absolute;
        left: 50%;
        bottom: -7px;
        width: 12px;
        height: 12px;
        border-radius: 2px;
        background: var(--tone);
        transform: translateX(-50%) rotate(45deg);
    }

    .h-ws__panel {
        margin-block-start: 14px;
        padding: 16px 16px 4px;
        border: 1px solid var(--tone-line);
        border-block-start: 3px solid var(--tone);
        border-radius: 12px;
        background: linear-gradient(180deg, var(--tone-bg), #fff 90px);
    }

    .h-ws__panel[hidden] { display: none; }
    .h-ws__panel:not([hidden]) { animation: hpTabIn .3s ease both; }
    .h-ws__panel:focus-visible { outline: 2px solid var(--tone); outline-offset: 2px; }

    @keyframes hpTabIn {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: none; }
    }

    .h-ws__lead { margin: 0 0 16px; font-size: .78rem; line-height: 1.6; color: var(--hp-muted); }

    .h-ws__timeline { margin: 0; padding: 0; list-style: none; }

    .h-ws__item {
        position: relative;
        padding-inline-start: 28px;
        padding-block-end: 18px;
    }

    .h-ws__item::before {
        content: "";
        position: absolute;
        inset-inline-start: 0;
        inset-block-start: 3px;
        width: 14px;
        height: 14px;
        box-sizing: border-box;
        border: 3px solid var(--tone);
        border-radius: 50%;
        background: #fff;
    }

    .h-ws__item::after {
        content: "";
        position: absolute;
        inset-inline-start: 6px;
        inset-block-start: 20px;
        inset-block-end: 2px;
        width: 2px;
        background: var(--tone-line);
    }

    .h-ws__item:last-child::after { display: none; }

    .h-ws__time {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border: 1px solid var(--tone-line);
        border-radius: 999px;
        background: var(--tone-bg);
        font-size: .72rem;
        font-weight: 700;
        color: var(--tone);
    }

    .h-ws__time i { font-size: .7rem; }

    .h-ws__title {
        margin: 7px 0 0;
        font-size: .88rem;
        font-weight: 600;
        line-height: 1.5;
        color: var(--hp-ink);
    }

    @media (max-width: 575.98px) {
        .h-ws__list { gap: 6px; }
        .h-ws__tab { padding-inline: 4px; font-size: .66rem; }
        .h-ws__panel { padding-inline: 12px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .h-slots-scroll { scroll-behavior: auto; }
        .h-scroll-hint { animation: none; }
        .h-schedule__panel:not([hidden]) .h-slot,
        .h-ws__panel:not([hidden]) { animation: none; }
        .h-ws__tab, .h-ws__ico { transition: none; }
        .h-ws__tab:hover { transform: none; }
    }
</style>

<script>
    (function () {
        /* ----- Day tabs: keep .h-day--current in sync with aria-selected ----- */
        document.querySelectorAll('[data-day-tabs] [role="tab"]').forEach(function (tab) {
            function sync() {
                tab.classList.toggle('h-day--current', tab.getAttribute('aria-selected') === 'true');
            }
            sync();
            new MutationObserver(sync).observe(tab, { attributes: true, attributeFilter: ['aria-selected'] });
        });

        /* ----- Scroll state: edge fades + hint ----- */
        document.querySelectorAll('[data-slots-wrap]').forEach(function (wrap) {
            var box = wrap.querySelector('[data-slots-scroll]');
            if (!box) { return; }

            function update() {
                if (!box.clientHeight) { return; } // panel is hidden
                wrap.dataset.scrolled = box.scrollTop > 8 ? 'true' : 'false';
                wrap.dataset.end = box.scrollTop + box.clientHeight >= box.scrollHeight - 6 ? 'true' : 'false';
            }

            box.addEventListener('scroll', update, { passive: true });
            window.addEventListener('resize', update);

            if ('ResizeObserver' in window) {
                new ResizeObserver(update).observe(box);   // fires when a day tab reveals it
            }
            update();
        });

        /* ----- Workshop parcours tabs ----- */
        document.querySelectorAll('[data-ws-tabs]').forEach(function (root) {
            var tabs = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));
            var rtl = getComputedStyle(root).direction === 'rtl';

            function activate(tab, focus) {
                tabs.forEach(function (t) {
                    var on = t === tab;
                    t.setAttribute('aria-selected', on ? 'true' : 'false');
                    t.tabIndex = on ? 0 : -1;
                    var panel = document.getElementById(t.getAttribute('aria-controls'));
                    if (panel) { panel.hidden = !on; }
                });
                if (focus) { tab.focus(); }
            }

            tabs.forEach(function (tab, i) {
                tab.addEventListener('click', function () { activate(tab, false); });

                tab.addEventListener('keydown', function (e) {
                    var fwd = rtl ? 'ArrowLeft' : 'ArrowRight';
                    var back = rtl ? 'ArrowRight' : 'ArrowLeft';
                    var next = null;

                    if (e.key === fwd)         { next = tabs[(i + 1) % tabs.length]; }
                    else if (e.key === back)   { next = tabs[(i - 1 + tabs.length) % tabs.length]; }
                    else if (e.key === 'Home') { next = tabs[0]; }
                    else if (e.key === 'End')  { next = tabs[tabs.length - 1]; }

                    if (next) { e.preventDefault(); activate(next, true); }
                });
            });
        });
    })();
</script>