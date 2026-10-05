{{--
    The basket summary, laid out for the browser's print dialog, in the ARABCIA 2026
    invoice design: A4 sheet on the violet artwork, glass cards, gradient table
    header and total pill, violet footer band.

    Assets (copy from the design pack to public/assets/images/invoice/):
        invoice-bg.png   (Fond_abstrait_violet_aux_motifs_géométriques)
        iia-maroc.png    (logo-iia-maroc)

    Standalone on purpose: no layouts.app, no stylesheet that can fail to load.
    Same payload as the PDF (CartController::printable()), so the totals agree.
--}}
@php
    $loc = $payload['invoiceLocale'];
    $direction = $loc === 'ar' ? 'rtl' : 'ltr';
    $ed = $payload['edition'];
    $organiser = $ed?->organiser ?? 'ARABCIA';
    $fmt = fn ($v) => \App\Support\Money::format($v, $payload['currency'], $loc);

    // Optional status pill: shown only when the translation key exists.
    $badge = \Illuminate\Support\Facades\Lang::has('order.invoice.proforma_badge', $loc)
        ? __('order.invoice.proforma_badge', [], $loc) : null;

    $icons = [
        'user'  => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'bank'  => '<path d="M3 10 12 4l9 6M5 10v8M9 10v8M15 10v8M19 10v8M3 21h18"/>',
        'doc'   => '<path d="M6 3h8l4 4v14H6zM14 3v4h4M9 13h6M9 17h6"/>',
        'card'  => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>',
        'cal'   => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M9 3v4M15 3v4"/>',
        'pin'   => '<path d="M12 21s7-6 7-12a7 7 0 0 0-14 0c0 6 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/>',
    ];
    $icon = fn ($k) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$icons[$k].'</svg>';
@endphp
<!DOCTYPE html>
<html lang="{{ $loc }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('order.invoice.proforma_title', [], $loc) }}</title>
    <style>
        :root {
            --deep: #2b1a78;
            --violet: #5b36c4;
            --violet-2: #7b4fd8;
            --ink: #1f1a4d;
            --muted: #6f6a96;
            --card: rgb(238 234 252 / 72%);
            --line: rgb(91 54 196 / 14%);
            --grad: linear-gradient(135deg, #3a2394 0%, #5b36c4 55%, #7b4fd8 100%);
        }

        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        body {
            background-image: url('../../../public/assets/images/invoice/bg.png');
            color: var(--ink);
            font-family: 'Aptos', 'Cairo', 'AvenirArabic', system-ui, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            padding: 1.5rem 0 3rem;
        }

        .ltr { direction: ltr; unicode-bidi: embed; text-align: left; }

        /* ---- The A4 sheet on the violet artwork ---- */
        .sheet {
            background: url('{{ asset('assets/images/invoice/invoice-bg.png') }}') center / 100% 100% no-repeat;
            border-radius: 6px;
            box-shadow: 0 18px 48px rgb(43 26 120 / 22%);
            margin: 0 auto;
            min-height: 297mm;
            overflow: hidden;
            padding: 13mm 11mm 48mm;
            position: relative;
            width: 210mm;
        }

        /* ---- Brand + title ---- */
        .brand { align-items: center; display: flex; gap: 10px; }
        .brand__mark {
            background: var(--grad); border-radius: 4px; color: #fff;
            display: grid; font-size: 22px; font-weight: 800; height: 44px; place-items: center; width: 44px;
        }
        .brand__name { color: var(--deep); font-size: 34px; font-weight: 900; letter-spacing: .5px; line-height: 1; }
        .brand__name span { color: var(--violet-2); }
        .brand__sub { color: var(--deep); font-size: 9px; letter-spacing: 5px; margin-top: 4px; text-transform: uppercase; }

        .top { align-items: stretch; display: flex; gap: 14px; justify-content: space-between; margin-top: 22px; }
        .top__title h1 {
            color: var(--deep); font-size: 40px; font-weight: 900; letter-spacing: -1px;
            line-height: 1; margin: 0; text-transform: uppercase;
        }
        .top__ref { align-items: center; display: flex; gap: 12px; margin-top: 10px; }
        .top__ref::after { background: var(--grad); border-radius: 3px; content: ''; height: 3px; width: 48px; }
        .top__ref b { font-size: 17px; font-weight: 500; }

        .pill {
            background: var(--grad); border-radius: 999px; box-shadow: 0 6px 14px rgb(91 54 196 / 30%);
            color: #fff; display: inline-block; font-size: 11px; font-weight: 800;
            letter-spacing: .5px; margin-top: 10px; padding: 7px 16px; text-transform: uppercase;
        }

        .top__meta { border-inline-start: 2px solid var(--deep); display: grid; gap: 12px; padding-inline-start: 16px; }
        .meta-item { align-items: center; display: flex; gap: 10px; }
        .chip {
            background: rgb(255 255 255 / 80%); border: 1px solid var(--line); border-radius: 10px;
            color: var(--violet); display: grid; flex: none; height: 38px; place-items: center; width: 38px;
        }
        .chip svg { height: 20px; width: 20px; }
        .meta-item b { display: block; font-size: 12px; }
        .meta-item span { color: var(--ink); font-size: 13px; }

        /* ---- Glass cards ---- */
        .card {
            background: var(--card); border: 1px solid var(--line); border-radius: 12px;
            padding: 12px 14px; break-inside: avoid;
        }
        .card--white { background: rgb(255 255 255 / 88%); }
        .card__head { align-items: center; color: var(--deep); display: flex; font-size: 14px; font-weight: 800; gap: 10px; margin-bottom: 8px; }
        .card__ico {
            background: var(--grad); border-radius: 9px; color: #fff;
            display: grid; flex: none; height: 32px; place-items: center; width: 32px;
        }
        .card__ico svg { height: 18px; width: 18px; }

        .duo { display: grid; gap: 12px; grid-template-columns: 1fr 1fr; margin-top: 18px; }
        .card p { margin: 0 0 2px; }
        .card .k { color: var(--muted); font-size: 12px; }
        .card .strong { font-weight: 800; }
        .issuer { align-items: center; display: flex; gap: 10px; justify-content: space-between; }
        .issuer img {  border-radius: 6px; height: 26px; padding: 2px 4px; }

        /* ---- Items ---- */
        .items-card { margin-top: 14px; }
        table { border-collapse: collapse; width: 100%; }
        .items { border-radius: 10px; overflow: hidden; }
        .items th {
            background: var(--grad); color: #fff; font-size: 12px; font-weight: 700;
            padding: 9px 12px; text-align: start;
        }
        .items td { background: rgb(255 255 255 / 70%); border-bottom: 1px solid var(--line); padding: 11px 12px; }
        .items tr:last-child td { border-bottom: 0; }
        .items .num { text-align: end; white-space: nowrap; }
        .items td.num:last-child { font-weight: 800; }

        /* ---- Totals + notes ---- */
        .bottom { align-items: start; display: grid; gap: 12px; grid-template-columns: 1fr 1fr; margin-top: 14px; }
        .totals { background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 12px; break-inside: avoid; }
        .totals table th, .totals table td { font-weight: 800; padding: 6px 8px; }
        .totals table th { text-align: start; }
        .totals table td { text-align: end; }
        .totals tr + tr th, .totals tr + tr td { border-top: 1px solid var(--line); }
        .grand {
            align-items: center; background: var(--grad); border-radius: 12px; color: #fff;
            display: flex; font-size: 16px; font-weight: 800; justify-content: space-between;
            margin-top: 8px; padding: 11px 16px;
        }

        .notes { color: var(--muted); font-size: 11.5px; }
        .banner { border-inline-start: 4px solid var(--violet-2); margin-top: 14px; }
        .banner strong { color: var(--deep); display: block; }

        /* ---- Footer band (sits on the artwork's violet band) ---- */
        .foot {
            align-items: center; bottom: 0; color: #fff; display: flex; font-size: 11px;
            gap: 18px; height: 38mm; inset-inline: 0; justify-content: space-between;
            padding: 0 11mm; position: absolute;
        }
        .foot__info { align-items: center; display: flex; gap: 12px; }
        .foot__info svg { flex: none; height: 20px; width: 20px; }
        .foot__tag {
            border-inline-start: 1px solid rgb(255 255 255 / 45%); font-size: 15px; font-style: italic;
            font-weight: 600; line-height: 1.3; max-width: 42mm; padding-inline-start: 16px;
        }

        /* ---- Screen-only toolbar ---- */
        .toolbar { display: flex; gap: .75rem; justify-content: center; margin: 0 auto 1.25rem; }
        .btn {
            background: var(--grad); border: 0; border-radius: 999px; color: #fff; cursor: pointer;
            font: inherit; font-size: 14px; font-weight: 700; padding: .65rem 1.4rem; text-decoration: none;
        }
        .btn--ghost { background: #fff; border: 1px solid var(--line); color: var(--deep); }

        @media (max-width: 800px) {
            body { padding: 0; }
            .sheet { border-radius: 0; transform-origin: top; width: 100%; padding: 1.25rem 1rem 12rem; }
            .top, .duo, .bottom { grid-template-columns: 1fr; flex-direction: column; }
            .top__meta { border: 0; padding: 0; }
            .foot { flex-direction: column; height: auto; padding: 1rem; position: static; background: var(--grad); margin: 1rem -1rem -12rem; }
        }

        @media print {
            @page { margin: 0; size: A4; }
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .sheet { border-radius: 0; box-shadow: none; height: 297mm; min-height: 0; width: 210mm; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <button type="button" class="btn" onclick="window.print()">@lang('order.print')</button>
    <a href="{{ route('cart.proforma') }}" class="btn btn--ghost">@lang('order.cart.invoice_download')</a>
    <a href="{{ route('cart') }}" class="btn btn--ghost">@lang('action.back')</a>
</div>

<main class="sheet">

    <header class="brand">
        <!-- <div class="brand__mark" aria-hidden="true">✺</div> -->
        <div>
            <!-- <div class="brand__name ltr">{{ $organiser }} <span>{{ $ed?->year ?? $ed?->starts_at?->format('Y') }}</span></div>
            <div class="brand__sub">{{ $ed?->titleIn(\App\Enums\Locale::parse($loc)) }}</div> -->
            <img src="{{asset('assets/images/invoice/logo-iia-maroc.png')}}"  width="200"/>
        </div>
    </header>

    <section class="top">
        <div class="top__title">
            <h1>@lang('order.invoice.proforma_title')</h1>
            <div class="top__ref">
                @if (filled($payload['reference']))
                    <b class="ltr">{{ $payload['reference'] }}</b>
                @endif
            </div>
            @if ($badge)
                <span class="pill">{{ $badge }}</span>
            @endif
        </div>

        <div class="top__meta">
            <div class="meta-item">
                <span class="chip">{!! $icon('cal') !!}</span>
                <div><b>@lang('order.invoice.issued_on')</b><span class="ltr">{{ $payload['issuedOn'] }}</span></div>
            </div>
            <div class="meta-item">
                <span class="chip">{!! $icon('doc') !!}</span>
                <div><b>@lang('order.invoice.method')</b><span>@lang('order.invoice.cmi')</span></div>
            </div>
        </div>
    </section>

    <section class="duo">
        <div class="card">
            <div class="card__head"><span class="card__ico">{!! $icon('user') !!}</span>@lang('order.invoice.billed_to')</div>
            @foreach ($payload['billedTo'] as $line)
                <p class="{{ $loop->first ? 'strong' : 'k ltr' }}">{{ $line }}</p>
            @endforeach
        </div>

        <div class="card">
            <div class="card__head"><span class="card__ico">{!! $icon('bank') !!}</span>@lang('order.invoice.details')</div>
            <div class="issuer">
                <div>
                    <p class="strong">{{ $ed?->host_institute ?? 'IIA Maroc' }}</p>
                    <p class="k">@lang('order.invoice.method')</p>
                    <p>@lang('order.invoice.cmi')</p>
                </div>
                <img src="{{ asset('assets/images/invoice/logo-iia-maroc.png') }}" alt="IIA Maroc">
            </div>
        </div>
    </section>

    <section class="card card--white items-card">
        <div class="card__head"><span class="card__ico">{!! $icon('doc') !!}</span>@lang('order.invoice.proforma_title')</div>
        <table class="items">
            <thead>
                <tr>
                    <th>@lang('order.ticket')</th>
                    <th class="num">@lang('order.quantity')</th>
                    <th class="num">@lang('order.amount')</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payload['lines'] as $line)
                    <tr>
                        <td>{{ $line['label'] }}</td>
                        <td class="num ltr">{{ $line['quantity'] }}</td>
                        <td class="num ltr">{{ $line['total'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="bottom">
        <div class="card notes">
            <div class="card__head"><span class="card__ico">{!! $icon('card') !!}</span>@lang('order.invoice.proforma_notes_title')</div>
            <p>@lang('order.invoice.proforma_note_estimate')</p>
            <p>@lang('order.invoice.proforma_note_issue')</p>
        </div>

        <div class="totals">
            <table>
                <tr><th>@lang('order.invoice.subtotal')</th><td class="ltr">{{ $fmt($payload['subtotal']) }}</td></tr>
                @if ($payload['discount'] > 0)
                    <tr><th>@lang('order.invoice.discount')</th><td class="ltr">&minus;{{ $fmt($payload['discount']) }}</td></tr>
                @endif
                @if ($payload['tax'] > 0)
                    <tr><th>@lang('order.invoice.tax')</th><td class="ltr">{{ $fmt($payload['tax']) }}</td></tr>
                @endif
            </table>
            <div class="grand"><span>@lang('order.total')</span><span class="ltr">{{ $fmt($payload['total']) }}</span></div>
        </div>
    </section>

    <p class="card banner notes">
        <strong>@lang('order.invoice.proforma_banner_title')</strong>
        @lang('order.invoice.proforma_banner_text')
    </p>

    <footer class="foot">
        <div class="foot__info">
            {!! $icon('pin') !!}
            <div>
                {{ $ed?->venue_name }} — {{ $ed?->city }}<br>
                @lang('order.invoice.footer', ['organiser' => $organiser])
            </div>
        </div>
        <div class="foot__tag">@lang('order.invoice.tagline')</div>
    </footer>

</main>

</body>
</html>