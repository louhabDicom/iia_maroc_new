{{--
    The proforma invoice.

    A document a delegate can take to their employer *before* paying, which is
    why it exists at all: procurement offices will not release funds against a
    screenshot of a basket.

    It is deliberately the worst of both worlds in two respects, and those two
    respects are the point:

      - it is headed "proforma", not "invoice", and carries a banner saying the
        numbered invoice is issued after payment. A delegate who forwards this
        to an accountant must not be able to pass it off as a paid document;
      - it is never numbered and never stored. `InvoiceService::generate()` is
        what allocates `invoice_number`, and only ever runs at settlement, so
        there is exactly one authoritative invoice per order and this document
        cannot become a second one.

    The layout mirrors `pdf.blade.php` on purpose. A proforma and an invoice that
    look nothing alike would be read as two different obligations rather than as
    one obligation at two stages.

    The locale is passed in as `$invoiceLocale` rather than read from the request,
    for the same reason the real invoice does it: the document is filed, not
    browsed, and has to stay in the language it was issued in.
--}}
@php
    $direction = $invoiceLocale === 'ar' ? 'rtl' : 'ltr';
    $end = $direction === 'rtl' ? 'left' : 'right';
@endphp
<!DOCTYPE html>
<html lang="{{ $invoiceLocale }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('order.invoice.proforma_title', [], $invoiceLocale) }}</title>
    <style>
        /* No external stylesheet: dompdf does not fetch remote CSS, and a
           stylesheet that silently failed to load is the classic reason a
           generated document arrives unformatted. */
        @page { margin: 30px 35px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.5;
        }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 8px; text-align: start; vertical-align: top; }

        .header { border-bottom: 3px solid #e39374; padding-bottom: 12px; margin-bottom: 14px; }
        .header td { border: 0; }

        .title { font-size: 18px; font-weight: bold; color: #2a1f6e; }
        .muted { color: #6b7280; }

        .ltr { direction: ltr; unicode-bidi: embed; }

        /* The banner. This is the single most important element on the page: it
           is what stops the document being filed as if it were paid, so it is
           sized like a warning rather than like a caption. */
        .banner {
            background: #fdf3ee;
            border: 1px solid #e39374;
            border-radius: 4px;
            color: #8a4622;
            font-size: 10px;
            margin-bottom: 18px;
            padding: 9px 11px;
        }
        .banner strong { display: block; font-size: 11px; margin-bottom: 3px; }

        .meta { margin-bottom: 18px; }
        .meta td { border: 0; padding: 2px 8px 2px 0; }

        .items th { background: #2a1f6e; color: #fff; border: 1px solid #2a1f6e; }
        .items td { border: 1px solid #d1d5db; }

        .totals { margin-top: 14px; width: 45%; margin-inline-start: auto; }
        .totals td { border: 0; }
        .totals .grand td {
            border-top: 2px solid #2a1f6e;
            font-weight: bold;
            font-size: 13px;
            padding-top: 8px;
        }

        .notes { margin-top: 20px; }
        .notes td { border: 0; padding: 2px 8px 2px 0; }

        .footer { margin-top: 24px; font-size: 9px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>

<table class="header">
    <tr>
        <td style="width: 55%;">
            <div class="title">{{ $edition?->organiser ?? 'ARABCIA' }}</div>
            <div class="muted">{{ $edition?->host_institute ?? 'IIA Maroc' }}</div>
            <div class="muted">{{ $edition?->titleIn(\App\Enums\Locale::parse($invoiceLocale)) }}</div>
        </td>
        <td style="text-align: {{ $end }};">
            <div style="font-size: 14px; font-weight: bold;">
                {{ __('order.invoice.proforma_title', [], $invoiceLocale) }}
            </div>
            @if (filled($reference))
                <div class="muted ltr">{{ $reference }}</div>
            @endif
        </td>
    </tr>
</table>

<div class="banner">
    <strong>{{ __('order.invoice.proforma_banner_title', [], $invoiceLocale) }}</strong>
    {{ __('order.invoice.proforma_banner_text', [], $invoiceLocale) }}
</div>

<table class="meta">
    <tr>
        <td style="width: 50%; vertical-align: top;">
            <strong>{{ __('order.invoice.billed_to', [], $invoiceLocale) }}</strong><br>
            @foreach ($billedTo as $line)
                <span class="{{ $loop->first ? '' : 'ltr' }}">{{ $line }}</span><br>
            @endforeach
        </td>
        <td style="vertical-align: top;">
            <strong>{{ __('order.invoice.details', [], $invoiceLocale) }}</strong><br>
            {{ __('order.invoice.issued_on', [], $invoiceLocale) }}:
            <span class="ltr">{{ $issuedOn }}</span><br>
            {{ __('order.invoice.method', [], $invoiceLocale) }}:
            {{ __('order.invoice.cmi', [], $invoiceLocale) }}
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th>{{ __('order.ticket', [], $invoiceLocale) }}</th>
            <th style="width: 90px;">{{ __('order.quantity', [], $invoiceLocale) }}</th>
            <th style="width: 130px;">{{ __('order.amount', [], $invoiceLocale) }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lines as $line)
            <tr>
                <td>{{ $line['label'] }}</td>
                <td class="ltr">{{ $line['quantity'] }}</td>
                <td class="ltr">{{ $line['total'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="totals">
    <tr>
        <td>{{ __('order.invoice.subtotal', [], $invoiceLocale) }}</td>
        <td class="ltr" style="text-align: {{ $end }};">{{ \App\Support\Money::format($subtotal, $currency, $invoiceLocale) }}</td>
    </tr>
    @if ($discount > 0)
        <tr>
            <td>{{ __('order.invoice.discount', [], $invoiceLocale) }}</td>
            <td class="ltr" style="text-align: {{ $end }};">−{{ \App\Support\Money::format($discount, $currency, $invoiceLocale) }}</td>
        </tr>
    @endif
    @if ($tax > 0)
        <tr>
            <td>{{ __('order.invoice.tax', [], $invoiceLocale) }}</td>
            <td class="ltr" style="text-align: {{ $end }};">{{ \App\Support\Money::format($tax, $currency, $invoiceLocale) }}</td>
        </tr>
    @endif
    <tr class="grand">
        <td>{{ __('order.total', [], $invoiceLocale) }}</td>
        <td class="ltr" style="text-align: {{ $end }};">{{ \App\Support\Money::format($total, $currency, $invoiceLocale) }}</td>
    </tr>
</table>

<table class="notes">
    <tr>
        <td>
            <strong>{{ __('order.invoice.proforma_notes_title', [], $invoiceLocale) }}</strong><br>
            {{ __('order.invoice.proforma_note_estimate', [], $invoiceLocale) }}<br>
            {{ __('order.invoice.proforma_note_issue', [], $invoiceLocale) }}
        </td>
    </tr>
</table>

<div class="footer">
    {{ $edition?->venue_name }} — {{ $edition?->city }}<br>
    {{ __('order.invoice.footer', ['organiser' => $edition?->organiser ?? 'ARABCIA'], $invoiceLocale) }}
</div>

</body>
</html>