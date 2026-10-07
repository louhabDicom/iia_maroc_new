{{--
    The basket summary, laid out for the browser's print dialog, in the ARABCIA
    2026 invoice design.

    Standalone on purpose: no layouts.app, no stylesheet that can fail to load.
    The sheet markup and styles live in resources/views/invoices/partials/ and
    are shared with the order page (pages/orders/show.blade.php), so the browser
    printout, the order page and the PDF all show one design from one payload.
--}}
@php
    $loc = $payload['invoiceLocale'];
    $direction = $loc === 'ar' ? 'rtl' : 'ltr';

    // Optional status pill: shown only when the translation key exists.
    $badge = \Illuminate\Support\Facades\Lang::has('order.invoice.proforma_badge', $loc)
        ? __('order.invoice.proforma_badge', [], $loc)
        : null;
@endphp
<!DOCTYPE html>
<html lang="{{ $loc }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('order.invoice.proforma_title', [], $loc) }}</title>
    @include('invoices.partials.sheet-styles')
</head>
<body>

<div class="invoice-doc invoice-doc--page">

    <div class="toolbar">
        <button type="button" class="btn" onclick="window.print()">@lang('order.print')</button>
        <a href="{{ route('cart.proforma') }}" class="btn btn--ghost">@lang('order.cart.invoice_download')</a>
        <a href="{{ route('cart') }}" class="btn btn--ghost">@lang('action.back')</a>
    </div>

    @include('invoices.partials.sheet', [
        'payload' => $payload,
        'heading' => __('order.invoice.proforma_title', [], $loc),
        'badge' => $badge,
        'isProforma' => true,
    ])

</div>

</body>
</html>