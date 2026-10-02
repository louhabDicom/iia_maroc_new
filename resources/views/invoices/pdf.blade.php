{{--
    The invoice PDF.

    Rendered from the order's snapshot columns only — `subtotal`, `total`, and
    the `order_items` price copies — and never from `ticket_types`. The 2024
    `facture.php` re-joined `iia_offre` and re-read the live price every time
    it was opened, so changing the tariff after a delegate had paid silently
    rewrote the figure on an already-issued document.

    The locale is the buyer's, not the request's: an invoice issued in French
    must stay in French when the customer later browses the site in Arabic, or
    their accounting department receives a document nobody there can read.
--}}
@php
    /** @var \App\Models\Order $order */
    $billing = (array) $order->billing;
    $direction = $invoiceLocale === 'ar' ? 'rtl' : 'ltr';
    $end = $direction === 'rtl' ? 'left' : 'right';
@endphp
<!DOCTYPE html>
<html lang="{{ $invoiceLocale }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('order.invoice.title', ['number' => $order->invoice_number], $invoiceLocale) }}</title>
    <style>
        /* No external stylesheet: dompdf does not fetch remote CSS, and a
           print stylesheet that silently failed to load is the classic reason
           a generated invoice arrives unformatted. */
        @page { margin: 30px 35px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.5;
        }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 8px; text-align: start; vertical-align: top; }

        .header { border-bottom: 3px solid #a1c514; padding-bottom: 12px; margin-bottom: 18px; }
        .header td { border: 0; }

        .title { font-size: 20px; font-weight: bold; color: #005991; }
        .muted { color: #6b7280; }

        /* A code, an email or an amount must not be reordered by the bidi
           algorithm when the invoice itself is right-to-left. */
        .ltr { direction: ltr; unicode-bidi: embed; }

        .meta { margin-bottom: 18px; }
        .meta td { border: 0; padding: 2px 8px 2px 0; }

        .items th { background: #005991; color: #fff; border: 1px solid #005991; }
        .items td { border: 1px solid #d1d5db; }

        .totals { margin-top: 14px; width: 45%; margin-inline-start: auto; }
        .totals td { border: 0; }
        .totals .grand td {
            border-top: 2px solid #005991;
            font-weight: bold;
            font-size: 13px;
            padding-top: 8px;
        }

        .participants { margin-top: 22px; }
        .participants th { background: #e5e7eb; border: 1px solid #d1d5db; }
        .participants td { border: 1px solid #d1d5db; }

        .footer { margin-top: 26px; font-size: 9px; color: #6b7280; text-align: center; }
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
                <div style="font-size: 15px; font-weight: bold;">
                    {{ __('order.invoice.title', ['number' => $order->invoice_number], $invoiceLocale) }}
                </div>
                <div class="muted ltr">{{ $order->reference }}</div>
                <div class="muted">{{ $order->status->label($invoiceLocale) }}</div>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <strong>{{ __('order.invoice.billed_to', [], $invoiceLocale) }}</strong><br>
                <span class="ltr">{{ trim(($billing['first_name'] ?? '').' '.($billing['last_name'] ?? '')) }}</span><br>
                @if (filled($billing['organisation'] ?? null)){{ $billing['organisation'] }}<br>@endif
                @if (filled($billing['address'] ?? null)){{ $billing['address'] }}<br>@endif
                @if (filled($billing['city'] ?? null)){{ $billing['city'] }}<br>@endif
                @if (filled($billing['country_iso2'] ?? null))<span class="ltr">{{ $billing['country_iso2'] }}</span><br>@endif
                @if (filled($billing['email'] ?? null))<span class="ltr">{{ $billing['email'] }}</span><br>@endif
                @if (filled($billing['phone'] ?? null))<span class="ltr">{{ $billing['phone'] }}</span>@endif
            </td>
            <td style="vertical-align: top;">
                <strong>{{ __('order.invoice.details', [], $invoiceLocale) }}</strong><br>
                {{ __('order.invoice.issued_on', [], $invoiceLocale) }}:
                <span class="ltr">{{ $order->paid_at?->format('d/m/Y') ?? $order->created_at->format('d/m/Y') }}</span><br>
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
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->label }}</td>
                    <td class="ltr">{{ $item->totalQuantity() }}</td>
                    <td class="ltr">{{ $item->formattedLineTotal($order->currency) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>{{ __('order.invoice.subtotal', [], $invoiceLocale) }}</td>
            <td class="ltr" style="text-align: {{ $end }};">{{ \App\Support\Money::format($order->subtotal, $order->currency, $invoiceLocale) }}</td>
        </tr>
        @if ($order->discount_total > 0)
            <tr>
                <td>{{ __('order.invoice.discount', [], $invoiceLocale) }}</td>
                <td class="ltr" style="text-align: {{ $end }};">−{{ \App\Support\Money::format($order->discount_total, $order->currency, $invoiceLocale) }}</td>
            </tr>
        @endif
        @if ($order->tax_total > 0)
            <tr>
                <td>{{ __('order.invoice.tax', [], $invoiceLocale) }}</td>
                <td class="ltr" style="text-align: {{ $end }};">{{ \App\Support\Money::format($order->tax_total, $order->currency, $invoiceLocale) }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td>{{ __('order.total', [], $invoiceLocale) }}</td>
            <td class="ltr" style="text-align: {{ $end }};">{{ \App\Support\Money::format($order->total, $order->currency, $invoiceLocale) }}</td>
        </tr>
    </table>

    <div class="participants">
        <strong>{{ __('order.participants', [], $invoiceLocale) }}</strong>
        <table>
            <thead>
                <tr>
                    <th>{{ __('order.participant_name', [], $invoiceLocale) }}</th>
                    <th>{{ __('register.job_title', [], $invoiceLocale) }}</th>
                    <th>{{ __('order.invoice.rate', [], $invoiceLocale) }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->participants as $participant)
                    <tr>
                        <td>{{ $participant->badgeLabel() }}</td>
                        <td>{{ $participant->job_title ?? '—' }}</td>
                        <td>{{ $participant->is_member ? __('pricing.member', [], $invoiceLocale) : __('pricing.standard', [], $invoiceLocale) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        {{ $edition?->venue_name }} — {{ $edition?->city }}<br>
        {{ __('order.invoice.footer', ['organiser' => $edition?->organiser ?? 'ARABCIA'], $invoiceLocale) }}
    </div>

</body>
</html>