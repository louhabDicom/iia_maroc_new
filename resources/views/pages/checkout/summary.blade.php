{{--
    The order summary.

    Rendered on its own so the JSON that a "+" click returns can repaint the total
    without reloading the page. Built from the same `$quote` as everything else on
    the page, so the figure that moves is the figure that will be charged rather
    than a second calculation kept in step by hand.
--}}
<table class="table table-sm">
    <caption class="visually-hidden">@lang('order.summary')</caption>
    <tbody>
        @foreach ($quote->lines as $line)
            <tr>
                <th scope="row" class="fw-normal text-start">
                    {{ $line['label'][$currentLocale->value] ?? '' }}
                </th>
                <td class="text-end text-nowrap">
                    @if ($line['member_quantity'] > 0)
                        <div class="small text-success">
                            {{ $line['member_quantity'] }} &times;
                            <span dir="ltr">{{ \App\Support\Money::format($line['unit_price_member'], $quote->currency) }}</span>
                        </div>
                    @endif
                    @if ($line['standard_quantity'] > 0)
                        <div class="small">
                            {{ $line['standard_quantity'] }} &times;
                            <span dir="ltr">{{ \App\Support\Money::format($line['unit_price_standard'], $quote->currency) }}</span>
                        </div>
                    @endif
                </td>
            </tr>
        @endforeach

        <tr class="border-top">
            <th scope="row" class="text-end">@lang('order.total')</th>
            <td class="text-end">
                {{-- `dir="ltr"`: an amount beside a currency code must not be
                     bidi-reordered on the Arabic page. --}}
                <span dir="ltr" class="fw-bold fs-5">
                    {{ $quote->formatted() }}
                </span>
            </td>
        </tr>
    </tbody>
</table>

@if ($quote->memberCount > 0)
    <p class="text-success small">
        @lang('order.member_saving_applied', ['count' => $quote->memberCount])
    </p>
@endif
