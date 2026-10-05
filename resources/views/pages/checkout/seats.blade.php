{{--
    Add or remove a place, one row per tariff in the basket.

    The participant count is not a preference, it is the number of seats the
    basket holds — `store()` refuses a form whose rows do not match. So these
    buttons do not add a blank row: they change the line's quantity, and the
    server sends back a re-quoted page where the name fields below have grown or
    shrunk to match and the total has moved.

    They are submit buttons with `formaction` on the buyer's own form, not links
    and not a JavaScript row builder. Three reasons, and the 2024 build had the
    last one wrong:

      - the price of a place cannot be computed on the client, because the member
        rate depends on an active Membership record
      - a buyer who has typed two names and then adds a third must not lose them,
        which is why the whole form travels with the click
      - with scripting off the post still works, and so does a screenshot pasted
        into a message

    `formnovalidate` is what lets the click through while the form is half-filled:
    without it the browser's own `required` fields refuse to submit.

    The JS in design.js intercepts these and swaps the regions in place. It is an
    upgrade over this, never a replacement for it — the `formaction` and the
    `value` are the real intent, and the script only reads them.
--}}
<div class="d-seats">
    <p class="d-seats__help">@lang('order.seats_help')</p>

    @foreach ($seatRows as $seat)
        <div class="d-seats__line" data-seats-line="{{ $seat['ticket_type_id'] }}">
            <div class="d-seats__label">
                <span class="d-seats__ticket">{{ $seat['name'] }}</span>

                @if ($seat['member_quantity'] > 0)
                    <span class="badge bg-success">@lang('pricing.member')</span>
                @endif
            </div>

            <div class="d-stepper"
                 role="group"
                 aria-label="{{ __('order.seats_for', ['ticket' => $seat['name']]) }}">
                <button type="submit"
                        class="d-stepper__btn"
                        name="adjust"
                        value="-{{ $seat['ticket_type_id'] }}"
                        formaction="{{ route('checkout.seats') }}"
                        formnovalidate
                        data-seats-btn="-{{ $seat['ticket_type_id'] }}"
                        aria-label="{{ __('order.remove_participant') }} — {{ $seat['name'] }}"
                        @disabled(! $seat['can_remove'])>
                    <i class="fas fa-minus" aria-hidden="true"></i>
                </button>

                {{-- The count, as text rather than an input: nothing here is
                     typed, and a spinner on a number nobody edits invites the
                     question of what it will do with a value it does not keep. --}}
                <span class="d-stepper__input" data-seats-count>{{ $seat['quantity'] }}</span>

                <button type="submit"
                        class="d-stepper__btn"
                        name="adjust"
                        value="+{{ $seat['ticket_type_id'] }}"
                        formaction="{{ route('checkout.seats') }}"
                        formnovalidate
                        data-seats-btn="+{{ $seat['ticket_type_id'] }}"
                        aria-label="{{ __('order.add_participant') }} — {{ $seat['name'] }}"
                        @disabled(! $seat['can_add'])>
                    <i class="fas fa-plus" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    @endforeach

    {{-- The count in words, with both forms in the markup because the script has
         to choose between them without a round trip. `:count` is left as the
         literal placeholder for that reason: substituting it here would leave
         nothing for the script to replace. --}}
    <p class="d-seats__total"
       data-seats-total
       data-count-one="{{ __('order.participant_count_one') }}"
       data-count-other="{{ __('order.participant_count', ['count' => ':count']) }}">
        @lang('order.participant_count', ['count' => $quote->participantCount])
    </p>
</div>
