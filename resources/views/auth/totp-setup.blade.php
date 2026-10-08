{{--
    Enrolling an authenticator app.

    The step numbers are not decoration. The failure this page has to survive is
    the visitor closing the tab halfway and coming back tomorrow, so it says
    plainly what has already happened and what has not: the account exists, the
    secret is not yet active, and nothing is lost by leaving and coming back.

    The QR code and the manual key are the same secret, printed side by side,
    rather than the key hidden behind a "can't scan?" link. The two people who
    need the key are exactly the ones who will not find the link: someone on a
    desktop with no phone camera to hand, and someone whose camera will not focus
    on a screen in a conference hall.

    The example apps are shown as plain tiles, not as a store badge wall. The
    visitor only has to recognise a name they may already have installed; any
    TOTP app works, and the page says so. The tiles are drawn inline so no
    third-party image is requested while the page holds a secret.
--}}
@extends('layouts.app')

@section('title', __('totp.title'))

@section('content')
    @php
        // Example authenticator apps. Add, remove or reorder freely.
        // 'glyph' picks a generic line icon from $glyphs, 'from'/'to' the tile gradient.
        // These are neutral stand-ins, not the official logos: to use the real ones,
        // replace the <svg> with <img src="{{ asset('img/2fa/google.svg') }}" alt="">.
        // 'logo' is a path under public/. If the file exists it is shown instead of the
        // generic glyph tile; if it does not, the glyph tile is the fallback.
        $apps = [
            ['name' => 'Google Authenticator',    'platforms' => 'iOS · Android', 'logo' => 'img/2fa/google-authenticator.svg',    'glyph' => 'shield', 'from' => '#4f8bf5', 'to' => '#2f5fd0'],
            ['name' => 'Microsoft Authenticator', 'platforms' => 'iOS · Android', 'logo' => 'img/2fa/microsoft-authenticator.svg', 'glyph' => 'lock',   'from' => '#2aa3e0', 'to' => '#0b6bb3'],
            ['name' => 'Authy',                   'platforms' => 'iOS · Android', 'logo' => 'img/2fa/authy.svg',                   'glyph' => 'ring',   'from' => '#ef5a5a', 'to' => '#c32f3f'],
            ['name' => '2FAS',                    'platforms' => 'iOS · Android', 'logo' => 'img/2fa/2fas.svg',                    'glyph' => 'grid',   'from' => '#6d3bd6', 'to' => '#4f3cc9'],
            ['name' => 'Aegis',                   'platforms' => 'Android',       'logo' => 'img/2fa/aegis.svg',                   'glyph' => 'key',    'from' => '#22b3a6', 'to' => '#0f857c'],
            ['name' => 'FreeOTP',                 'platforms' => 'iOS · Android', 'logo' => 'img/2fa/freeotp.svg',                 'glyph' => 'clock',  'from' => '#f39a3d', 'to' => '#d36f12'],
        ];

        $glyphs = [
            'shield' => '<path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/>',
            'lock'   => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/>',
            'ring'   => '<path d="M12 3a9 9 0 109 9"/><circle cx="12" cy="12" r="2"/>',
            'grid'   => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><path d="M14 14h6v6h-6z"/>',
            'key'    => '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M16 7l3 3"/>',
            'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        ];

        // Base32 secret shown in groups of four: easier to read aloud and to type.
        // The copy button still copies the raw, ungrouped value.
        $manualKeyDisplay = trim(chunk_split((string) $manualKey, 4, ' '));
    @endphp

    <style>
        .totp-page {
            --ink: #1b1464;
            --brand: #4f3cc9;
            --deep: #2f1f9c;
            --muted: #5f6384;
            --line: #e4e1f4;
            --surface: #f6f5fd;
            --accent: #6d3bd6;
            --shadow: 0 10px 30px rgba(47, 31, 156, .10);
        }

        /* ---------- Stepper ---------- */
        .totp-steps {
            list-style: none;
            margin: 0;
            padding: 0;
            counter-reset: totp-step;
        }
        .totp-steps > li {
            position: relative;
            counter-increment: totp-step;
            padding-inline-start: 3.25rem;
            padding-block-end: 1.75rem;
        }
        .totp-steps > li:last-child { padding-block-end: 0; }

        .totp-steps > li::before {
            content: counter(totp-step);
            position: absolute;
            inset-inline-start: 0;
            inset-block-start: 0;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: .9rem;
            color: #fff;
            background: linear-gradient(135deg, #2b1d9a, #3a27b3);
            box-shadow: 0 4px 12px rgba(47, 31, 156, .28);
        }
        /* Continuous rail between the numbers. */
        .totp-steps > li:not(:last-child)::after {
            content: "";
            position: absolute;
            inset-inline-start: calc(1.125rem - 1px);
            inset-block-start: 2.6rem;
            inset-block-end: .35rem;
            width: 2px;
            background: var(--line);
            border-radius: 2px;
        }

        /* ---------- Example apps ---------- */
        .totp-apps-label {
            margin: 1rem 0 .5rem;
            font-size: .74rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .totp-apps {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: .6rem;
        }
        .totp-app {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .55rem .75rem;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 10px;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
        }
        .totp-app:hover {
            border-color: #cfc9f0;
            box-shadow: 0 6px 18px rgba(47, 31, 156, .10);
            transform: translateY(-1px);
        }
        .totp-app__icon {
            flex: 0 0 auto;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            color: #fff;
            box-shadow: 0 4px 10px rgba(27, 20, 100, .18);
        }
        .totp-app__icon--logo {
            background: transparent;
            box-shadow: none;
            overflow: hidden;
        }
        .totp-app__icon--logo img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .totp-app__icon svg {
            width: 22px;
            height: 22px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .totp-app__text { min-width: 0; line-height: 1.25; }
        .totp-app__name {
            display: block;
            font-size: .84rem;
            font-weight: 700;
            color: var(--ink);
            overflow-wrap: anywhere;
        }
        .totp-app__meta {
            display: block;
            font-size: .72rem;
            color: var(--muted);
        }

        /* ---------- QR ---------- */
        .totp-qr {
            display: inline-block;
            margin-block-start: .9rem;
            padding: .75rem;
            /* Always white, even on a dark theme: a scanner needs the quiet zone. */
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        .totp-qr img { display: block; max-width: 100%; height: auto; }

        /* ---------- Manual key ---------- */
        .totp-keybox {
            display: flex;
            align-items: stretch;
            flex-wrap: wrap;
            gap: .5rem;
            margin-block-start: .75rem;
        }
        .totp-key {
            flex: 1 1 14rem;
            margin: 0;
            padding: .7rem .9rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 1.05rem;
            font-weight: 600;
            letter-spacing: .12em;
            color: var(--deep);
            background: var(--surface);
            border: 1px dashed #cfc9f0;
            border-radius: 8px;
            word-break: break-all;
            user-select: all;
        }
        .totp-copy {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .55rem 1rem;
            font-size: .8rem;
            font-weight: 600;
            color: var(--deep);
            background: #eef0fb;
            border: 1px solid transparent;
            border-radius: 999px;
            cursor: pointer;
            transition: background .15s ease;
        }
        .totp-copy:hover { background: #e2e5f9; }
        .totp-copy svg {
            width: 16px;
            height: 16px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .totp-copy.is-done { color: #0f7b4a; background: #e3f6ec; }
        .totp-copy-status {
            flex-basis: 100%;
            min-height: 1.1rem;
            margin: 0;
            font-size: .78rem;
            color: var(--muted);
        }

        /* ---------- Code field ---------- */
        .totp-confirm .app-code {
            text-align: center;
            font-size: 1.5rem;
            letter-spacing: .4em;
            font-variant-numeric: tabular-nums;
        }

        /* ---------- Focus (visible, AA) ---------- */
        .totp-page a:focus-visible,
        .totp-page button:focus-visible,
        .totp-page input:focus-visible {
            outline: 3px solid var(--accent);
            outline-offset: 2px;
        }

        @media (max-width: 575.98px) {
            .totp-steps > li { padding-inline-start: 2.9rem; }
            .totp-apps { grid-template-columns: 1fr; }
            .totp-key { font-size: .95rem; letter-spacing: .08em; }
            .totp-copy { width: 100%; justify-content: center; }
        }
        @media (prefers-reduced-motion: reduce) {
            .totp-app { transition: none; }
            .totp-app:hover { transform: none; }
        }
    </style>

    <div class="section-padding-04 totp-page">
        <div class="container">
            <div class="app-shell app-shell--narrow">

                <h1 class="app-title">@lang('totp.heading')</h1>

                <p class="app-lede">@lang('totp.lede')</p>

                {{-- The state of the account, stated before the instructions
                     rather than discovered at the end. --}}
                <div class="app-notice mb-4" role="status">
                    @lang('totp.pending_notice')
                </div>

                <div class="app-card">
                    <ol class="totp-steps">

                        {{-- 1. Install --}}
                        <li>
                            <p class="app-note--strong">@lang('totp.step_install')</p>
                            <p class="app-note">@lang('totp.step_install_lede')</p>

                            <p class="totp-apps-label" id="totp-apps-label">@lang('totp.apps_label')</p>
                            <ul class="totp-apps" aria-labelledby="totp-apps-label">
                                @foreach ($apps as $app)
                                    <li class="totp-app">
                                        @if (! empty($app['logo']) && file_exists(public_path($app['logo'])))
                                            <span class="totp-app__icon totp-app__icon--logo" aria-hidden="true">
                                                <img src="{{ asset($app['logo']) }}" alt="" width="40" height="40" loading="lazy" decoding="async">
                                            </span>
                                        @else
                                            <span class="totp-app__icon"
                                                  aria-hidden="true"
                                                  style="background: linear-gradient(135deg, {{ $app['from'] }}, {{ $app['to'] }});">
                                                <svg viewBox="0 0 24 24" focusable="false">{!! $glyphs[$app['glyph']] !!}</svg>
                                            </span>
                                        @endif
                                        <span class="totp-app__text">
                                            <span class="totp-app__name">{{ $app['name'] }}</span>
                                            <span class="totp-app__meta">{{ $app['platforms'] }}</span>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                            <p class="app-note mt-2">@lang('totp.apps_note')</p>
                        </li>

                        {{-- 2. Scan --}}
                        <li>
                            <p class="app-note--strong">@lang('totp.step_scan')</p>
                            <p class="app-note">@lang('totp.step_scan_lede', ['issuer' => $issuer])</p>

                            @if ($qrCode !== '')
                                <div class="totp-qr">
                                    {{-- Inline SVG data URL, never a served image:
                                         this URI contains the shared secret, and a
                                         separate request would leave it in a cache
                                         where a shared machine could read it back. --}}
                                    <img src="{{ $qrCode }}"
                                         alt="{{ __('totp.qr_alt', ['issuer' => $issuer]) }}"
                                         width="240"
                                         height="240">
                                </div>
                            @endif
                        </li>

                        {{-- 3. Manual key --}}
                        <li>
                            <p class="app-note--strong">@lang('totp.step_manual')</p>
                            <p class="app-note">@lang('totp.step_manual_lede')</p>

                            <div class="totp-keybox">
                                {{-- Forced LTR: the secret is base32, and bidi would
                                     present it in reverse on the Arabic page. --}}
                                <p class="totp-key" id="totp-key" dir="ltr" aria-label="{{ __('totp.key_label') }}">{{ $manualKeyDisplay }}</p>

                                <button type="button"
                                        class="totp-copy"
                                        id="totp-copy"
                                        data-value="{{ $manualKey }}"
                                        data-label="{{ __('totp.copy') }}"
                                        data-done="{{ __('totp.copied') }}"
                                        data-failed="{{ __('totp.copy_failed') }}">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <rect x="9" y="9" width="11" height="11" rx="2"/>
                                        <path d="M5 15V6a2 2 0 012-2h9"/>
                                    </svg>
                                    <span data-copy-text>@lang('totp.copy')</span>
                                </button>

                                <p class="totp-copy-status" id="totp-copy-status" aria-live="polite"></p>
                            </div>
                        </li>

                        {{-- 4. Confirm --}}
                        <li>
                            <p class="app-note--strong">@lang('totp.step_confirm')</p>

                            <form method="POST" action="{{ route('totp.confirm') }}" class="totp-confirm mt-2">
                                @csrf

                                <x-form.field name="code" :label="__('totp.code_label')" :help="__('totp.code_hint', ['digits' => $codeLength])" required>
                                    {{-- One field, not six boxes: six separate inputs break
                                         paste-to-advance, block paste, and lose the value
                                         when autofill fills the first box. --}}
                                    <input type="text"
                                           name="code"
                                           id="totp-code"
                                           required
                                           inputmode="numeric"
                                           pattern="[0-9]*"
                                           autocomplete="one-time-code"
                                           autocapitalize="off"
                                           spellcheck="false"
                                           maxlength="{{ $codeLength }}"
                                           dir="ltr"
                                           @class(['input', 'app-code', 'input-error' => $errors->has('code')])
                                           @if ($errors->has('code')) aria-invalid="true" aria-describedby="code-error" @endif>
                                </x-form.field>

                                <button type="submit" class="btn btn-primary w-100">@lang('totp.submit')</button>
                            </form>
                        </li>
                    </ol>
                </div>

                {{-- Stated up front because it is the trade this method makes:
                     free and unlimited, but a delegate without a spare handset
                     and without the recovery codes is locked out. --}}
                <p class="app-note mt-4 text-center">@lang('totp.tradeoff')</p>
            </div>
        </div>
    </div>

    <script>
        (function () {
            'use strict';

            // Code field: digits only, whether typed or pasted ("123 456" works).
            var code = document.getElementById('totp-code');
            if (code) {
                code.addEventListener('input', function () {
                    var max = parseInt(code.getAttribute('maxlength'), 10) || 6;
                    var clean = code.value.replace(/\D/g, '').slice(0, max);
                    if (clean !== code.value) { code.value = clean; }
                });
            }

            // Copy the raw secret (not the grouped display) to the clipboard.
            var btn = document.getElementById('totp-copy');
            var status = document.getElementById('totp-copy-status');
            if (!btn) { return; }

            var label = btn.querySelector('[data-copy-text]');
            var timer = null;

            function say(message, ok) {
                status.textContent = message;
                label.textContent = ok ? btn.dataset.done : btn.dataset.label;
                btn.classList.toggle('is-done', ok);
                clearTimeout(timer);
                timer = setTimeout(function () {
                    status.textContent = '';
                    label.textContent = btn.dataset.label;
                    btn.classList.remove('is-done');
                }, 2500);
            }

            function legacyCopy(text) {
                var area = document.createElement('textarea');
                area.value = text;
                area.setAttribute('readonly', '');
                area.style.position = 'fixed';
                area.style.insetBlockStart = '-1000px';
                document.body.appendChild(area);
                area.select();
                var ok = false;
                try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
                document.body.removeChild(area);
                return ok;
            }

            btn.addEventListener('click', function () {
                var text = btn.dataset.value;
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(
                        function () { say(btn.dataset.done, true); },
                        function () {
                            legacyCopy(text) ? say(btn.dataset.done, true) : say(btn.dataset.failed, false);
                        }
                    );
                } else {
                    legacyCopy(text) ? say(btn.dataset.done, true) : say(btn.dataset.failed, false);
                }
            });
        })();
    </script>
@endsection