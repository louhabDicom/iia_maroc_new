{{--
    The basket summary, laid out for the browser's print dialog.

    A standalone document rather than a section of the cart page: `layouts.app`
    carries the header, the footer and the cookie banner, none of which belong on
    a sheet of paper handed to a finance department. The same payload the PDF is
    rendered from is passed in — see CartController::printable() — so the two
    documents cannot disagree about the total.

    The print button is hidden by `@media print` rather than removed, so it is
    reachable by keyboard and announced by a screen reader on screen, and does not
    survive onto the paper.
--}}
@php
    /* The same direction the payload was rendered in, not the request's, so the
       printout and the PDF it sits next to are the same document. */
    $direction = $payload['invoiceLocale'] === 'ar' ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html lang="{{ $payload['invoiceLocale'] }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('order.invoice.proforma_title', [], $payload['invoiceLocale']) }}</title>
    <style>
        /* Self-contained, like the PDF template: no stylesheet can fail to load
           here because there is only this one. */
        :root {
            --ink: #1f2937;
            --muted: #6b7280;
            --navy: #2a1f6e;
            --clay: #e39374;
            --line: #d1d5db;
        }

        * { box-sizing: border-box; }

        body {
            background: #f6f5fb;
            color: var(--ink);
            font-family: 'Aptos', 'Cairo', 'AvenirArabic', system-ui, sans-serif;
            font-size: 15px;
            line-height: 1.6;
            margin: 0;
            padding: 2rem 1rem 4rem;
        }

        .sheet {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 12px 32px rgb(28 25 64 / 10%);
            margin: 0 auto;
            max-width: 780px;
            padding: 2.5rem;
        }

        /* A code, an address or an amount must not be reordered by the bidi
           algorithm when the document itself is right-to-left. */
        .ltr { direction: ltr; unicode-bidi: embed; text-align: left; }

        .head {
            align-items: flex-start;
            border-bottom: 3px solid var(--clay);
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: space-between;
            padding-bottom: 1.25rem;
        }

        .org { font-size: 1.25rem; font-weight: 800; color: var(--navy); }
        .muted { color: var(--muted); font-size: 0.875rem; }

        .doc-title { font-size: 1.05rem; font-weight: 700; text-align: end; }

        .banner {
            background: #fdf3ee;
            border: 1px solid var(--clay);
            border-radius: 8px;
            color: #8a4622;
            font-size: 0.8125rem;
            margin-top: 1.25rem;
            padding: 0.75rem 1rem;
        }

        .banner strong { display: block; font-size: 0.875rem; margin-bottom: 0.2rem; }

        .meta {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            margin-top: 1.75rem;
        }

        .meta dt { font-size: 0.8125rem; font-weight: 700; margin-bottom: 0.35rem; }
        .meta dd { margin: 0 0 0.15rem; }

        table { border-collapse: collapse; margin-top: 1.75rem; width: 100%; }

        .items th {
            background: var(--navy);
            color: #fff;
            font-size: 0.8125rem;
            padding: 0.6rem 0.75rem;
            text-align: start;
        }

        .items td { border: 1px solid var(--line); padding: 0.65rem 0.75rem; }
        .items .num { text-align: end; }

        .totals { margin-top: 1rem; width: min(320px, 100%); margin-inline-start: auto; }

        .totals th, .totals td { padding: 0.35rem 0.5rem; }
        .totals th { font-weight: 400; text-align: start; }
        .totals td { text-align: end; }

        .totals .grand th, .totals .grand td {
            border-top: 2px solid var(--navy);
            font-size: 1.05rem;
            font-weight: 800;
            padding-top: 0.6rem;
        }

        .notes { color: var(--muted); font-size: 0.8125rem; margin-top: 1.75rem; }

        .foot { color: var(--muted); font-size: 0.75rem; margin-top: 2rem; text-align: center; }

        /* On screen only. */
        .toolbar {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            margin: 0 auto 1.5rem;
            max-width: 780px;
        }

        .btn {
            background: var(--navy);
            border: 0;
            border-radius: 999px;
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-size: 0.9375rem;
            font-weight: 700;
            padding: 0.7rem 1.5rem;
            text-decoration: none;
        }

        .btn--ghost { background: transparent; border: 1px solid var(--line); color: var(--navy); }

        @media print {
            /* The sheet loses its screen furniture: no shadow, no rounded
               corners, no page background. Every printer drops the background
               anyway unless told otherwise, and asking for it wastes ink on a
               full A4 of purple. */
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .sheet { border-radius: 0; box-shadow: none; max-width: none; padding: 0; }

            @page { margin: 16mm; }

            /* Keep a line and its figures on the same sheet. */
            tr, .banner, .meta, .notes { break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <button type="button" class="btn" onclick="window.print()">
        <i class="fas fa-print" aria-hidden="true"></i>
        @lang('order.print')
    </button>

    <a href="{{ route('cart.proforma') }}" class="btn btn--ghost">
        <i class="fas fa-download" aria-hidden="true"></i>
        @lang('order.cart.invoice_download')
    </a>

    <a href="{{ route('cart') }}" class="btn btn--ghost">@lang('action.back')</a>
</div>

<main class="sheet">

    <header class="head">
        <div>
            <div class="org">{{ $payload['edition']?->organiser ?? 'ARABCIA' }}</div>
            <div class="muted">{{ $payload['edition']?->host_institute ?? 'IIA Maroc' }}</div>
            <div class="muted">
                {{ $payload['edition']?->titleIn(\App\Enums\Locale::parse($payload['invoiceLocale'])) }}
            </div>
        </div>

        <div class="doc-title">
            <div>@lang('order.invoice.proforma_title')</div>
            @if (filled($payload['reference']))
                <div class="muted ltr">{{ $payload['reference'] }}</div>
            @endif
        </div>
    </header>

    <p class="banner">
        <strong>@lang('order.invoice.proforma_banner_title')</strong>
        @lang('order.invoice.proforma_banner_text')
    </p>

    <dl class="meta">
        <div>
            <dt>@lang('order.invoice.billed_to')</dt>
            <dd>
                @foreach ($payload['billedTo'] as $line)
                    <span class="{{ $loop->first ? '' : 'ltr' }}">{{ $line }}</span><br>
                @endforeach
            </dd>
        </div>

        <div>
            <dt>@lang('order.invoice.details')</dt>
            <dd>
                @lang('order.invoice.issued_on'): <span class="ltr">{{ $payload['issuedOn'] }}</span><br>
                @lang('order.invoice.method'): @lang('order.invoice.cmi')
            </dd>
        </div>
    </dl>

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

    <table class="totals">
        <tr>
            <th>@lang('order.invoice.subtotal')</th>
            <td class="ltr">{{ \App\Support\Money::format($payload['subtotal'], $payload['currency'], $payload['invoiceLocale']) }}</td>
        </tr>

        @if ($payload['discount'] > 0)
            <tr>
                <th>@lang('order.invoice.discount')</th>
                <td class="ltr">&minus;{{ \App\Support\Money::format($payload['discount'], $payload['currency'], $payload['invoiceLocale']) }}</td>
            </tr>
        @endif

        @if ($payload['tax'] > 0)
            <tr>
                <th>@lang('order.invoice.tax')</th>
                <td class="ltr">{{ \App\Support\Money::format($payload['tax'], $payload['currency'], $payload['invoiceLocale']) }}</td>
            </tr>
        @endif

        <tr class="grand">
            <th>@lang('order.total')</th>
            <td class="ltr">{{ \App\Support\Money::format($payload['total'], $payload['currency'], $payload['invoiceLocale']) }}</td>
        </tr>
    </table>

    <p class="notes">
        <strong>@lang('order.invoice.proforma_notes_title')</strong><br>
        @lang('order.invoice.proforma_note_estimate')<br>
        @lang('order.invoice.proforma_note_issue')
    </p>

    <p class="foot">
        {{ $payload['edition']?->venue_name }} — {{ $payload['edition']?->city }}<br>
        @lang('order.invoice.footer', ['organiser' => $payload['edition']?->organiser ?? 'ARABCIA'])
    </p>

</main>

</body>
</html>