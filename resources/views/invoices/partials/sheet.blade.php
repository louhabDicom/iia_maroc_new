{{--
    Shared invoice sheet body (A4 artwork sheet, glass cards, gradient table
    header and total pill, violet footer band) in the ARABCIA 2026 invoice
    design. Used by the standalone print pages (pages/cart-print.blade.php) and
    the in-app order page (pages/orders/show.blade.php) so a single payload and
    a single markup produce the on-screen and printed document.

    Expects:
        $payload    array        see InvoiceService::orderPayload()/cartPayload()
        $heading    string       document heading (proforma vs numbered invoice)
        $badge      string|null  optional status pill
        $isProforma bool         true for a pre-payment (provisional) document
--}}
@php
    $locale = $payload['invoiceLocale'];
    $edition = $payload['edition'];
    $organiser = $edition?->organiser ?? 'ARABCIA';
    $fmt = fn ($value) => \App\Support\Money::format($value, $payload['currency'], $locale);

    $paths = [
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'bank' => '<path d="M3 10 12 4l9 6M5 10v8M9 10v8M15 10v8M19 10v8M3 21h18"/>',
        'doc' => '<path d="M6 3h8l4 4v14H6zM14 3v4h4M9 13h6M9 17h6"/>',
        'card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>',
        'cal' => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M9 3v4M15 3v4"/>',
        'pin' => '<path d="M12 21s7-6 7-12a7 7 0 0 0-14 0c0 6 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/>',
    ];
    $icon = fn (string $key) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths[$key].'</svg>';
@endphp

<article class="sheet" role="document">

    <header class="brand">
        <div>
            <img src="{{ asset('assets/images/invoice/logo-iia-maroc.png') }}" alt="{{ $organiser }}" width="200">
        </div>
    </header>

    <section class="top">
        <div class="top__title">
            <h1>{{ $heading }}</h1>
            <div class="top__ref">
                @if (filled($payload['reference']))
                    <b class="ltr">{{ $payload['reference'] }}</b>
                @endif
            </div>
            @if (filled($badge))
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
                    <p class="strong">{{ $edition?->host_institute ?? 'IIA Maroc' }}</p>
                    <p class="k">@lang('order.invoice.method')</p>
                    <p>@lang('order.invoice.cmi')</p>
                </div>
                <img src="{{ asset('assets/images/invoice/logo-iia-maroc.png') }}" alt="IIA Maroc">
            </div>
        </div>
    </section>

    <section class="card card--white items-card">
        <div class="card__head"><span class="card__ico">{!! $icon('doc') !!}</span>{{ $heading }}</div>
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
        @if ($isProforma)
            <div class="card notes">
                <div class="card__head"><span class="card__ico">{!! $icon('card') !!}</span>@lang('order.invoice.proforma_notes_title')</div>
                <p>@lang('order.invoice.proforma_note_estimate')</p>
                <p>@lang('order.invoice.proforma_note_issue')</p>
            </div>
        @else
            <div class="card notes">
                <div class="card__head"><span class="card__ico">{!! $icon('card') !!}</span>@lang('order.invoice.details')</div>
                <p class="strong">{{ $edition?->host_institute ?? 'IIA Maroc' }}</p>
                <p class="k">@lang('order.invoice.method')</p>
                <p>@lang('order.invoice.cmi')</p>
            </div>
        @endif

        <div class="totals">
            <table>
                <tbody>
                    <tr>
                        <th>@lang('order.invoice.subtotal')</th>
                        <td class="ltr">{{ $fmt($payload['subtotal']) }}</td>
                    </tr>
                    @if (($payload['discount'] ?? 0) > 0)
                        <tr>
                            <th>@lang('order.invoice.discount')</th>
                            <td class="ltr">&minus;{{ $fmt($payload['discount']) }}</td>
                        </tr>
                    @endif
                    @if (($payload['tax'] ?? 0) > 0)
                        <tr>
                            <th>@lang('order.invoice.tax')</th>
                            <td class="ltr">{{ $fmt($payload['tax']) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
            <div class="grand">
                <span>@lang('order.total')</span>
                <span class="ltr">{{ $fmt($payload['total']) }}</span>
            </div>
        </div>
    </section>

    @if ($isProforma)
        <p class="card banner notes">
            <strong>@lang('order.invoice.proforma_banner_title')</strong>
            @lang('order.invoice.proforma_banner_text')
        </p>
    @endif

    <footer class="foot">
        <div class="foot__info">
            {!! $icon('pin') !!}
            <div>
                {{ $edition?->venue_name }} — {{ $edition?->city }}<br>
                @lang('order.invoice.footer', ['organiser' => $organiser])
            </div>
        </div>
    </footer>

</article>