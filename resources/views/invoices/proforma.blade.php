{{--
    The proforma invoice, in the ARABCIA 2026 invoice design.

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

    It shares the design language of `pages/cart-print.blade.php` â€” the same
    violet system, glass cards, violet table header, total bar and footer band
    â€” so a proforma and an invoice that look nothing alike would be read as two
    different obligations rather than as one obligation at two stages.

    THE LAYOUT IS NOT THE SAME MARKUP, and that is deliberate. This is rendered
    by dompdf (see `InvoiceService::streamProforma()`), not by a browser, and
    dompdf implements roughly CSS 2.1. Concretely, none of the following work
    here, so none of them appear below:

      - `display: flex` and `display: grid`      -> every two-column block is a
                                                   `<table>`;
      - `gap`                                     -> cell padding;
      - CSS custom properties (`var(--grad)`)     -> literals;
      - `linear-gradient()`                       -> a solid violet. dompdf has no
                                                   gradient support, so the gradient
                                                   is the one thing in the browser
                                                   design that cannot be reproduced
                                                   and is approximated by the flat
                                                   mid-tone;
      - `inset-inline-*`, logical properties      -> `left`/`right` chosen per
                                                   direction;
      - inline `<svg>`                           -> Unicode marks in DejaVu Sans,
                                                   which is the one font family
                                                   guaranteed to be present and
                                                   the only one here with the
                                                   Arabic coverage;
      - `background-size: cover` on the page      -> the violet artwork is not
                                                   reproduced at all. dompdf
                                                   cannot scale a background image,
                                                   and an unscaled 2 MB PNG tiled
                                                   across the sheet is worse than
                                                   a clean field of colour.

    Images therefore use `public_path()`, never `asset()`: `enable_remote` is
    false in the dompdf config, so an `http://localhost/...` URL does not fail
    loudly â€” it silently renders nothing.

    The locale is passed in as `$invoiceLocale` rather than read from the
    request, for the same reason the real invoice does it: the document is
    filed, not browsed, and has to stay in the language it was issued in.
--}}
@php
    $direction = $invoiceLocale === 'ar' ? 'rtl' : 'ltr';
    $end = $direction === 'rtl' ? 'left' : 'right';
    $start = $direction === 'rtl' ? 'right' : 'left';

    // Local path, not asset(): see the note above on enable_remote.
    $logo = public_path('assets/images/invoice/logo-iia-maroc.png');
    $logoExists = is_file($logo);

    $fmt = fn ($value) => \App\Support\Money::format($value, $currency, $invoiceLocale);

    // Marks in place of the browser design's inline SVG chips. DejaVu Sans
    // covers all of them, so none of this degrades to a tofu box.
    $marks = [
        'user' => 'â—',
        'bank' => 'â– ',
        'doc'  => 'â—†',
        'cal'  => 'â˜…',
        'card' => 'âœ¦',
        'pin'  => 'â—†',
    ];
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
        @page { margin: 0; }

        body {
            background-color: #2b1a78;
            color: #1f1a4d;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5pt;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }

        table { border-collapse: collapse; width: 100%; }
        td, th { padding: 0; text-align: {{ $start }}; vertical-align: top; }

        .ltr { direction: ltr; unicode-bidi: embed; }

        /* ---- The sheet ----
           The browser design is an A4 sheet floating on the violet artwork.
           In a PDF the sheet *is* the page, so the padding that used to be
           the sheet's own becomes the page's. It is tight because the document
           is meant to arrive as one sheet: the footer band follows the content
           rather than sitting under it, so every millimetre here is a
           millimetre the band does not get. */
        .pad { padding: 10mm 10mm 2mm; }

        .band { background-color: #201062; color: #fff; }
        .band td { color: #fff; }

        /* ---- Brand ---- */
        .brand img { height: 46px; }

        .rule {
            background-color: #5b36c4;
            height: 3px;
            margin: 4mm 0 0 0;
        }

        /* ---- Title block ---- */
        .title { color: #2b1a78; font-size: 22pt; font-weight: bold; letter-spacing: -1px; }
        .title-rule { background-color: #5b36c4; height: 3px; margin-top: 3mm; width: 20mm; }

        .reference {
            color: #2b1a78;
            font-size: 12pt;
            font-weight: bold;
            margin-top: 2mm;
        }

        /* The browser pill is a gradient capsule. A solid one reads the same at
           print size and is the only capsule dompdf can draw. */
        .pill {
            background-color: #5b36c4;
            border-radius: 20px;
            color: #fff;
            display: inline-block;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: .5px;
            margin-top: 3mm;
            padding: 4px 12px;
        }

        /* ---- Meta chips ---- */
        .chip {
            background-color: #5b36c4;
            border-radius: 8px;
            color: #fff;
            font-size: 9pt;
            height: 30px;
            text-align: center;
            vertical-align: middle;
            width: 30px;
        }
        .meta-key { color: #2b1a78; font-size: 8.5pt; font-weight: bold; }
        .meta-val { color: #1f1a4d; font-size: 9.5pt; }

        /* ---- Glass cards ----
           rgba() over the artwork becomes a flat tint, because there is no
           artwork behind it to composite against. */
        .card {
            background-color: #eeeafc;
            border: 1px solid #d6cdf2;
            border-radius: 10px;
        }
        .card--white { background-color: #ffffff; }
        .card__head { color: #2b1a78; font-size: 11pt; font-weight: bold; padding-bottom: 3px; }
        .card__ico {
            color: #5b36c4;
            font-size: 11pt;
            padding-{{ $end }}: 8px;
        }

        .gap { height: 3mm; }
        .gap-lg { height: 5mm; }

        .k { color: #6f6a96; font-size: 9pt; }
        .strong { font-weight: bold; }

        /* ---- Items ---- */
        .items th {
            background-color: #5b36c4;
            border: 1px solid #5b36c4;
            color: #fff;
            font-size: 9pt;
            font-weight: bold;
            padding: 7px 10px;
        }
        .items td {
            background-color: #fbfaff;
            border-bottom: 1px solid #d6cdf2;
            padding: 9px 10px;
        }
        .items .num { text-align: {{ $end }}; white-space: nowrap; }
        .items td.num:last-child { font-weight: bold; }

        /* ---- Totals ---- */
        .totals { background-color: #eeeafc; border: 1px solid #d6cdf2; border-radius: 10px; }
        .totals th { font-weight: bold; padding: 5px 8px; text-align: {{ $start }}; }
        .totals td { padding: 5px 8px; text-align: {{ $end }}; }
        .totals tr + tr th, .totals tr + tr td { border-top: 1px solid #d6cdf2; }

        /* The total bar. Gradient in the browser, solid here. */
        .grand {
            background-color: #5b36c4;
            border-radius: 10px;
            color: #fff;
            font-size: 12pt;
            font-weight: bold;
        }
        .grand td { color: #fff; padding: 9px 12px; }

        /* ---- Banner ----
           The most important element on the page: it is what stops the
           document being filed as if it were paid. The browser design sets it
           as a left rule on a muted card; here it is a full tint block, so it
           cannot be mistaken for ordinary body copy. */
        .banner {
            background-color: #fdf3ee;
            border: 1px solid #e39374;
            border-{{ $start }}: 4px solid #7b4fd8;
            border-radius: 6px;
        }
        .banner strong { color: #8a4622; display: block; }
        .banner span { color: #8a4622; font-size: 9pt; }

        .notes { color: #6f6a96; font-size: 9pt; }

        /* ---- Footer band ----
           In the document flow, as the last table. It was tried as a
           `position: fixed` band, which is what a browser invoice would use and
           what repeats the band across pages, but dompdf emits an empty page for
           each fixed element it lays out before the real content: with a
           full-bleed background and `@page { margin: 0 }` a two-line band
           produced three pages, two of them blank but for the band. A band that
           follows the content is wrong on an overflow page and right everywhere
           else; phantom blank pages are wrong always. */
        .foot {
            background-color: #201062;
            color: #fff;
            font-size: 8.5pt;
            padding: 5mm 10mm;
        }
        .foot td { color: #fff; }
        .foot__mark { color: #e39374; font-size: 11pt; padding-{{ $end }}: 8px; }
        .foot__tag {
            border-{{ $start }}: 1px solid #6f6a96;
            font-size: 10.5pt;
            font-style: italic;
            font-weight: bold;
            padding-{{ $start }}: 12px;
        }
    </style>
</head>
<body>

{{-- The sheet. `--spacer` exists only because dompdf ignores vertical-align on
     a cell whose sibling has no height, which is what collapses the gutter
     columns below. --}}
<table>
    <tr>
        <td class="pad">

            <table>
                <tr>
                    <td style="width: 8mm;"></td>
                    <td>

                        {{-- ---- Brand ---- --}}
                        <table>
                            <tr>
                                <td class="brand">
                                    @if ($logoExists)
                                        <img src="{{ $logo }}" alt="IIA Maroc">
                                    @else
                                        <span class="strong" style="font-size: 15pt; color: #2b1a78;">
                                            {{ $edition?->organiser ?? 'ARABCIA' }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        <table class="rule">
                            <tr><td></td></tr>
                        </table>

                        <table>
                            <tr>
                                <td style="width: 58%; padding-top: 6mm;">
                                    <div class="title">{{ __('order.invoice.proforma_title', [], $invoiceLocale) }}</div>
                                    <table class="title-rule">
                                        <tr><td></td></tr>
                                    </table>
                                    @if (filled($reference))
                                        <div class="reference ltr">{{ $reference }}</div>
                                    @endif
                                    <span class="pill">
                                        {{ __('order.invoice.proforma_banner_title', [], $invoiceLocale) }}
                                    </span>
                                </td>
                                <td style="vertical-align: bottom;">

                                    <table>
                                        <tr>
                                            <td class="chip">{{ $marks['cal'] }}</td>
                                            <td style="padding-{{ $start }}: 8px;">
                                                <div class="meta-key">{{ __('order.invoice.issued_on', [], $invoiceLocale) }}</div>
                                                <div class="meta-val ltr">{{ $issuedOn }}</div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="chip" style="padding-top: 8px;">{{ $marks['doc'] }}</td>
                                            <td style="padding: 8px 0 0 8px;">
                                                <div class="meta-key">{{ __('order.invoice.method', [], $invoiceLocale) }}</div>
                                                <div class="meta-val">{{ __('order.invoice.cmi', [], $invoiceLocale) }}</div>
                                            </td>
                                        </tr>
                                    </table>

                                </td>
                            </tr>
                        </table>

                        <div class="gap-lg"></div>

                        {{-- ---- Cards ---- --}}
                        <table>
                            <tr>
                                <td style="width: 50%;" class="card">
                                    <table>
                                        <tr>
                                            <td class="card__ico">{{ $marks['user'] }}</td>
                                            <td class="card__head">{{ __('order.invoice.billed_to', [], $invoiceLocale) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="padding-top: 2px;">
                                                @foreach ($billedTo as $line)
                                                    <div class="{{ $loop->first ? 'strong' : 'k' }} {{ $loop->first ? '' : 'ltr' }}">
                                                        {{ $line }}
                                                    </div>
                                                @endforeach
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="width: 4mm;"></td>
                                <td style="width: 50%;" class="card">
                                    <table>
                                        <tr>
                                            <td class="card__ico">{{ $marks['bank'] }}</td>
                                            <td class="card__head">{{ __('order.invoice.details', [], $invoiceLocale) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="padding-top: 2px;">
                                                <div class="strong">{{ $edition?->host_institute ?? 'IIA Maroc' }}</div>
                                                <div class="k">{{ __('order.invoice.method', [], $invoiceLocale) }}</div>
                                                <div>{{ __('order.invoice.cmi', [], $invoiceLocale) }}</div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <div class="gap"></div>

                        {{-- ---- Items ---- --}}
                        <table class="card card--white">
                            <tr>
                                <td style="padding: 12px 14px;">
                                    <table style="width: 100%;">
                                        <tr>
                                            <td class="card__ico">{{ $marks['doc'] }}</td>
                                            <td class="card__head">{{ __('order.invoice.proforma_title', [], $invoiceLocale) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="padding-top: 8px;">
                                                <table class="items">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('order.ticket', [], $invoiceLocale) }}</th>
                                                            <th class="num" style="width: 28mm;">{{ __('order.quantity', [], $invoiceLocale) }}</th>
                                                            <th class="num" style="width: 42mm;">{{ __('order.amount', [], $invoiceLocale) }}</th>
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

                        {{-- ---- Notes + totals ---- --}}
                        <table>
                            <tr>
                                <td style="width: 54%;" class="card">
                                    <table>
                                        <tr>
                                            <td class="card__ico">{{ $marks['card'] }}</td>
                                            <td class="card__head">{{ __('order.invoice.proforma_notes_title', [], $invoiceLocale) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="notes" style="padding-top: 2px;">
                                                {{ __('order.invoice.proforma_note_estimate', [], $invoiceLocale) }}<br>
                                                {{ __('order.invoice.proforma_note_issue', [], $invoiceLocale) }}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="width: 4mm;"></td>
                                <td style="width: 46%;" class="totals">
                                    <table style="width: 100%;">
                                        <tr>
                                            <th>{{ __('order.invoice.subtotal', [], $invoiceLocale) }}</th>
                                            <td class="ltr">{{ $fmt($subtotal) }}</td>
                                        </tr>
                                        @if ($discount > 0)
                                            <tr>
                                                <th>{{ __('order.invoice.discount', [], $invoiceLocale) }}</th>
                                                <td class="ltr">&minus;{{ $fmt($discount) }}</td>
                                            </tr>
                                        @endif
                                        @if ($tax > 0)
                                            <tr>
                                                <th>{{ __('order.invoice.tax', [], $invoiceLocale) }}</th>
                                                <td class="ltr">{{ $fmt($tax) }}</td>
                                            </tr>
                                        @endif
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <div class="gap"></div>

                        {{-- The total, on its own full-width bar: it is the one
                             figure the document exists to convey. --}}
                        <table class="grand" style="width: 100%;">
                            <tr>
                                <td style="width: 60%;">{{ __('order.total', [], $invoiceLocale) }}</td>
                                <td class="ltr" style="text-align: {{ $end }}; font-size: 14pt;">{{ $fmt($total) }}</td>
                            </tr>
                        </table>

                        <div class="gap"></div>

                        {{-- ---- Banner ---- --}}
                        <table class="banner" style="width: 100%;">
                            <tr>
                                <td style="padding: 9px 11px;">
                                    <strong>{{ __('order.invoice.proforma_banner_title', [], $invoiceLocale) }}</strong>
                                    <span>{{ __('order.invoice.proforma_banner_text', [], $invoiceLocale) }}</span>
                                </td>
                            </tr>
                        </table>

                    </td>
                    <td style="width: 8mm;"></td>
                </tr>
            </table>

        </td>
    </tr>
</table>

{{-- ---- Footer band ----
     A fixed block rather than a table row: dompdf repeats a fixed element on
     every page, so an over-long basket keeps its band. --}}
<div class="foot">
    <table>
        <tr>
            <td style="width: 62%;">
                <table>
                    <tr>
                        <td class="foot__mark">{{ $marks['pin'] }}</td>
                        <td>
                            {{ $edition?->venue_name }} â€” {{ $edition?->city }}
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            {{ __('order.invoice.footer', ['organiser' => $edition?->organiser ?? 'ARABCIA'], $invoiceLocale) }}
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 38%;">
                <table>
                    <tr>
                        <td class="foot__tag">
                            {{ $edition?->titleIn(\App\Enums\Locale::parse($invoiceLocale)) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

</body>
</html>