{{--
    The proforma invoice (dompdf), in the ARABCIA 2026 invoice design.

    Same rules as before: headed "proforma", never numbered, never stored.
    The numbered invoice only exists after settlement (InvoiceService::generate()).

    WHY THE "PHP GD extension is required" ERROR IS GONE
    dompdf can only embed PNG through the GD extension. This template uses JPEG
    only, which dompdf reads natively, so it renders on a server without GD.
    Copy to public/assets/images/invoice/ :
        invoice-bg.jpg        the violet artwork (full A4, replaces the PNG)
        logo-iia-maroc.jpg    the IIA Maroc logo on white (replaces the PNG)
    If a file is missing the page still renders (flat colour / text fallback).

    dompdf is ~CSS 2.1: tables instead of flex/grid, literals instead of
    variables, solid violet instead of gradients, local paths (public_path)
    instead of URLs. The artwork is an absolutely-positioned <img>, not a
    `position: fixed` one (fixed elements make dompdf emit blank pages) and not
    a CSS background (no background-size support).
--}}
@php
    $direction = $invoiceLocale === 'ar' ? 'rtl' : 'ltr';
    $end = $direction === 'rtl' ? 'left' : 'right';
    $start = $direction === 'rtl' ? 'right' : 'left';

    $art = public_path('assets/images/invoice/invoice-bg.png');
    $logo = public_path('assets/images/invoice/logo-iia-maroc.png');
    $noImages = (bool) ($noImages ?? false);
    $artExists = ! $noImages && is_file($art) && str_ends_with($art, '.jpg');
    $logoExists = ! $noImages && is_file($logo) && str_ends_with($logo, '.jpg');

    $organiser = $edition?->organiser ?? 'ARABCIA';
    $year = $edition?->year ?? now()->year;
    $fmt = fn ($value) => \App\Support\Money::format($value, $currency, $invoiceLocale);
    $t = fn (string $key, array $r = []) => __($key, $r, $invoiceLocale);

    // Optional status capsule: shown only if the translation key exists.
    $badge = \Illuminate\Support\Facades\Lang::has('order.invoice.proforma_badge', $invoiceLocale)
        ? $t('order.invoice.proforma_badge') : null;
    $tagline = \Illuminate\Support\Facades\Lang::has('order.invoice.tagline', $invoiceLocale)
        ? $t('order.invoice.tagline') : $edition?->titleIn(\App\Enums\Locale::parse($invoiceLocale));

    // Unicode marks present in DejaVu Sans (no inline SVG in dompdf).
    $m = ['user' => '●', 'bank' => '■', 'doc' => '▤', 'cal' => '▦', 'card' => '✦', 'pin' => '◆'];
@endphp
<!DOCTYPE html>
<html lang="{{ $invoiceLocale }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    <title>{{ $t('order.invoice.proforma_title') }}</title>
    <style>
        @page { margin: 0; size: A4 portrait; }

        body {
            color: #1f1a4d;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        table { border-collapse: collapse; width: 100%; }
        td, th { padding: 0; text-align: {{ $start }}; vertical-align: top; }
        .ltr { direction: ltr; unicode-bidi: embed; }
        .strong { font-weight: bold; }
        .k { color: #6f6a96; font-size: 8.5pt; }

        /* Artwork + footer layer: absolute, behind everything. */
        .art { left: 0; position: absolute; top: 0; z-index: -1; }
        .band-fallback { background-color: #3a2394; height: 38mm; left: 0; position: absolute; top: 259mm; width: 210mm; z-index: -1; }

        .page { padding: 11mm 11mm 0; }

        /* Brand */
        .mark {  color: #fff; font-size: 22pt; height: 15mm; text-align: center; vertical-align: middle; width: 15mm; }
        .brand-name { color: #2b1a78; font-size: 26pt; font-weight: bold; letter-spacing: 1px; line-height: 1; }
        .brand-year { color: #7b4fd8; }
        .brand-sub { color: #2b1a78; font-size: 6.5pt; letter-spacing: 3px; padding-top: 2mm; text-transform: uppercase; }

        /* Title */
        .title { color: #2b1a78; font-size: 27pt; font-weight: bold; letter-spacing: -1px; line-height: 1; text-transform: uppercase; }
        .title-rule { background-color: #7b4fd8; height: 3px; margin-top: 3mm; width: 22mm; }
        .reference { color: #2b1a78; font-size: 11pt; margin-top: 3mm; }
        .pill { background-color: #5b36c4; border-radius: 14px; color: #fff; display: inline-block; font-size: 8pt; font-weight: bold; margin-top: 3mm; padding: 4px 14px; text-transform: uppercase; }

        .meta { border-{{ $start }}: 2px solid #2b1a78; padding-{{ $start }}: 6mm; }
        .chip { background-color: #e6e0fa; border: 1px solid #d6cdf2; border-radius: 8px; color: #5b36c4; font-size: 12pt; height: 11mm; text-align: center; vertical-align: middle; width: 11mm; }
        .meta-key { color: #2b1a78; font-size: 8.5pt; font-weight: bold; }
        .meta-val { font-size: 10pt; }

        /* Cards */
        .card { background-color: #eeeafc; border: 1px solid #d6cdf2; border-radius: 10px; }
        .card--white { background-color: #fff; }
        .ico { background-color: #5b36c4; border-radius: 7px; color: #fff; font-size: 10pt; height: 8mm; text-align: center; vertical-align: middle; width: 8mm; }
        .head { color: #2b1a78; font-size: 11pt; font-weight: bold; padding-{{ $start }}: 8px; vertical-align: middle; }
        .gap { height: 3.5mm; }

        /* Items */
        .items th { background-color: #5b36c4; color: #fff; font-size: 8.5pt; padding: 7px 10px; }
        .items td { background-color: #fbfaff; border-bottom: 1px solid #d6cdf2; padding: 9px 10px; }
        .num { text-align: {{ $end }}; white-space: nowrap; }
        .items td.num:last-child { font-weight: bold; }

        /* Totals */
        .tot th { font-weight: bold; padding: 5px 8px; text-align: {{ $start }}; }
        .tot td { font-weight: bold; padding: 5px 8px; text-align: {{ $end }}; }
        .grand { background-color: #3a2394; border-radius: 10px; color: #fff; font-size: 12pt; font-weight: bold; }
        .grand td { color: #fff; padding: 9px 14px; }

        .banner { background-color: #fdf3ee; border: 1px solid #e39374; border-{{ $start }}: 4px solid #7b4fd8; border-radius: 6px; }
        .banner strong { color: #8a4622; display: block; }
        .banner span { color: #8a4622; font-size: 8.5pt; }
        .notes { color: #6f6a96; font-size: 8.5pt; }

        /* Footer text, laid over the artwork's violet band (A4: band starts ~258mm). */
        .foot { color: #fff; font-size: 8pt; left: 11mm; position: absolute; right: 11mm; top: 266mm; }
        .foot td { color: #fff; vertical-align: middle; }
        .foot__mark { color: #e39374; font-size: 11pt; padding-{{ $end }}: 8px; }
        .foot__tag { border-{{ $start }}: 1px solid #a99be0; font-size: 10pt; font-style: italic; font-weight: bold; padding-{{ $start }}: 10px; }
    </style>
</head>
<body>

@if ($artExists)
    <img class="art" src="{{ $art }}" style="width: 210mm; height: 297mm;" alt="">
@else
    <div class="band-fallback"></div>
@endif

<div class="page">

    {{-- Brand --}}
    <table>
        <tr>
            <td class="mark" style="width: 15mm;">
                <img src="{{ $logo }}"  width="200"/>
            </td>
            <td style="padding-{{ $start }}: 4mm; vertical-align: middle;">
                <!-- <div class="brand-name ltr">{{ $organiser }} <span class="brand-year">{{ $year }}</span></div>
                <div class="brand-sub">{{ $edition?->titleIn(\App\Enums\Locale::parse($invoiceLocale)) }}</div> -->
            </td>
        </tr>
    </table>

    <div class="gap"></div>

    {{-- Title + meta --}}
    <table>
        <tr>
            <td style="width: 55%; padding-top: 4mm;">
                <div class="title">{{ $t('order.invoice.proforma_title') }}</div>
                <table class="title-rule" style="width: 22mm;"><tr><td></td></tr></table>
                @if (filled($reference))
                    <div class="reference ltr">{{ $reference }}</div>
                @endif
                @if ($badge)
                    <span class="pill">{{ $badge }}</span>
                @endif
            </td>
            <td class="meta" style="padding-top: 4mm;">
                <table>
                    <tr>
                        <td class="chip">{{ $m['cal'] }}</td>
                        <td style="padding-{{ $start }}: 3mm; vertical-align: middle;">
                            <div class="meta-key">{{ $t('order.invoice.issued_on') }}</div>
                            <div class="meta-val ltr">{{ $issuedOn }}</div>
                        </td>
                    </tr>
                    <tr><td colspan="2" style="height: 3mm;"></td></tr>
                    <tr>
                        <td class="chip">{{ $m['doc'] }}</td>
                        <td style="padding-{{ $start }}: 3mm; vertical-align: middle;">
                            <div class="meta-key">{{ $t('order.invoice.method') }}</div>
                            <div class="meta-val">{{ $t('order.invoice.cmi') }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="gap"></div>

    {{-- Billed to / Issuer --}}
    <table>
        <tr>
            <td class="card" style="width: 49%; padding: 11px 13px;">
                <table>
                    <tr>
                        <td class="ico">{{ $m['user'] }}</td>
                        <td class="head">{{ $t('order.invoice.billed_to') }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top: 6px;">
                            @foreach ($billedTo as $line)
                                <div class="{{ $loop->first ? 'strong' : 'k ltr' }}">{{ $line }}</div>
                            @endforeach
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 2%;"></td>
            <td class="card" style="width: 49%; padding: 11px 13px;">
                <table>
                    <tr>
                        <td class="ico">{{ $m['bank'] }}</td>
                        <td class="head">{{ $t('order.invoice.details') }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top: 6px;">
                            <div class="strong">{{ $edition?->host_institute ?? 'IIA Maroc' }}</div>
                            <div class="k">{{ $t('order.invoice.method') }}</div>
                            <div>{{ $t('order.invoice.cmi') }}</div>
                            @if ($logoExists)
                                <img src="{{ $logo }}" alt="IIA Maroc" style="height: 22px; margin-top: 4px;">
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="gap"></div>

    {{-- Items --}}
    <table class="card card--white">
        <tr>
            <td style="padding: 11px 13px;">
                <table>
                    <tr>
                        <td class="ico">{{ $m['doc'] }}</td>
                        <td class="head">{{ $t('order.invoice.proforma_title') }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top: 8px;">
                            <table class="items">
                                <thead>
                                    <tr>
                                        <th>{{ $t('order.ticket') }}</th>
                                        <th class="num" style="width: 26mm;">{{ $t('order.quantity') }}</th>
                                        <th class="num" style="width: 40mm;">{{ $t('order.amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lines as $line)
                                        <tr>
                                            <td>{{ $line['label'] }}</td>
                                            <td class="num ltr">{{ $line['quantity'] }}</td>
                                            <td class="num ltr">{{ $line['total'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="gap"></div>

    {{-- Notes + totals --}}
    <table>
        <tr>
            <td class="card" style="width: 52%; padding: 11px 13px;">
                <table>
                    <tr>
                        <td class="ico">{{ $m['card'] }}</td>
                        <td class="head">{{ $t('order.invoice.proforma_notes_title') }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="notes" style="padding-top: 6px;">
                            {{ $t('order.invoice.proforma_note_estimate') }}<br>
                            {{ $t('order.invoice.proforma_note_issue') }}
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 3%;"></td>
            <td style="width: 45%;">
                <table class="card" style="width: 100%;">
                    <tr>
                        <td style="padding: 6px 4px;">
                            <table class="tot">
                                <tr>
                                    <th>{{ $t('order.invoice.subtotal') }}</th>
                                    <td class="ltr">{{ $fmt($subtotal) }}</td>
                                </tr>
                                @if ($discount > 0)
                                    <tr>
                                        <th>{{ $t('order.invoice.discount') }}</th>
                                        <td class="ltr">&minus;{{ $fmt($discount) }}</td>
                                    </tr>
                                @endif
                                @if ($tax > 0)
                                    <tr>
                                        <th>{{ $t('order.invoice.tax') }}</th>
                                        <td class="ltr">{{ $fmt($tax) }}</td>
                                    </tr>
                                @endif
                            </table>
                        </td>
                    </tr>
                </table>
                <div style="height: 2.5mm;"></div>
                <table class="grand">
                    <tr>
                        <td>{{ $t('order.total') }}</td>
                        <td class="ltr" style="text-align: {{ $end }}; font-size: 13pt;">{{ $fmt($total) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="gap"></div>

    {{-- Banner: keeps the document from being filed as a paid invoice. --}}
    <table class="banner">
        <tr>
            <td style="padding: 8px 11px;">
                <strong>{{ $t('order.invoice.proforma_banner_title') }}</strong>
                <span>{{ $t('order.invoice.proforma_banner_text') }}</span>
            </td>
        </tr>
    </table>

</div>

{{-- Footer text over the violet band --}}
<table class="foot">
    <tr>
        <td style="width: 62%;">
            <table>
                <tr>
                    <td class="foot__mark">{{ $m['pin'] }}</td>
                    <td>{{ $edition?->venue_name }} — {{ $edition?->city }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td>{{ $t('order.invoice.footer', ['organiser' => $organiser]) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>