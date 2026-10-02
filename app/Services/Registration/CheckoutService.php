<?php

declare(strict_types=1);

namespace App\Services\Registration;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Edition;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Participant;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Turns a cart into an order.
 *
 * Everything that must be decided at the moment of purchase happens here and
 * only here: prices are snapshotted, the membership rate is granted or
 * refused, capacity is checked, and the participants are written. After this
 * returns, the order is a historical record that no later price change, ticket
 * edit or member cancellation can alter.
 *
 * The 2024 equivalent (`process_form.php`) interpolated the whole form into
 * SQL strings, trusted a `POST['adherent1']` checkbox for the member price,
 * and inserted the order before the participants with no transaction, so a
 * failure halfway left a paid order with nobody on it.
 */
class CheckoutService
{
    /**
     * Build the order for a cart without persisting it.
     *
     * Separate from {@see place()} so the checkout page can show the buyer an
     * authoritative total — including the member price actually granted —
     * before they commit to it. The figure on the cart is only an estimate;
     * this is the number the card will be charged.
     */
    public function quote(Cart $cart, User $user): Quote
    {
        $items = $cart->items()->with('ticketType')->get();

        if ($items->isEmpty()) {
            throw new RuntimeException('Cannot quote an empty cart.');
        }

        // A cart holds one currency. The 2024 basket mixed tariffs in a single
        // order and then priced the whole thing with the first row's currency,
        // which is how a USD order ended up charged in MAD.
        $currencies = $items
            ->pluck('ticketType.currency')
            ->map(fn (?string $c): string => strtoupper((string) $c))
            ->unique();

        if ($currencies->count() > 1) {
            throw new RuntimeException(
                'A single order cannot mix currencies. Split the basket by currency.'
            );
        }

        $currency = (string) $currencies->first();

        $lines = [];
        $subtotal = 0;
        $memberCount = 0;
        $standardCount = 0;

        foreach ($items as $item) {
            $ticket = $item->ticketType;

            if ($ticket === null) {
                // The ticket was withdrawn while it sat in a basket. Skipped
                // rather than fatal, so one retired tariff does not strand
                // everything else in the cart.
                continue;
            }

            $memberQty = $this->grantedMemberQuantity($item, $user);
            $standardQty = max(0, $item->quantity - $memberQty);

            $lineTotal = ($ticket->price_member * $memberQty)
                + ($ticket->price_standard * $standardQty);

            $lines[] = [
                'ticket_type_id' => $ticket->getKey(),
                'label' => $ticket->getRawOriginal('name'),
                'unit_price_member' => $ticket->price_member,
                'unit_price_standard' => $ticket->price_standard,
                'member_quantity' => $memberQty,
                'standard_quantity' => $standardQty,
                'line_total' => $lineTotal,
            ];

            $subtotal += $lineTotal;
            $memberCount += $memberQty;
            $standardCount += $standardQty;
        }

        if ($lines === []) {
            throw new RuntimeException('Every ticket in the cart is no longer available.');
        }

        return new Quote(
            lines: $lines,
            subtotal: $subtotal,
            total: $subtotal,
            currency: $currency,
            memberCount: $memberCount,
            standardCount: $standardCount,
            participantCount: $memberCount + $standardCount,
            ticketType: TicketType::query()->find($lines[0]['ticket_type_id']),
        );
    }

    /**
     * How many of this line's places may be priced at the member rate.
     *

    /**
     * Create and persist the order, its lines and its participants.
     *
     * One transaction, with the capacity check inside it: two people checking
     * out the last remaining place at the same moment must produce one order
     * and one refusal, not two orders for one place. The 2024 build checked
     * nothing and happily sold past its own 300-delegate cap.
     *
     * @param  array<int, array{full_name: string, job_title?: ?string, email?: ?string, phone?: ?string, is_member?: bool, badge_name?: ?string}>  $participants
     * @param  array<string, mixed>  $billing
     */
    public function place(
        Cart $cart,
        User $user,
        Quote $quote,
        array $participants,
        array $billing,
        ?string $ip = null,
    ): Order {
        return DB::transaction(function () use ($cart, $user, $quote, $participants, $billing, $ip): Order {
            $edition = Edition::current();

            $this->assertCapacityAvailable($edition, $quote->participantCount);
            $this->assertParticipantCount($participants, $quote);

            $order = Order::query()->create([
                'edition_id' => $edition?->getKey(),
                'user_id' => $user->getKey(),
                'ticket_type_id' => $quote->ticketType?->getKey(),
                'status' => OrderStatus::Pending,
                'payment_driver' => (string) config('cmi.driver', 'test'),
                'billing' => $billing,
                'subtotal' => $quote->subtotal,
                'discount_total' => 0,
                'tax_total' => 0,
                'total' => $quote->total,
                'currency' => $quote->currency,
                'currency_numeric' => $this->numericCurrency($quote->currency),
                'member_count' => $quote->memberCount,
                'standard_count' => $quote->standardCount,
                'ip_address' => $ip,
            ]);

            foreach ($quote->lines as $line) {
                OrderItem::query()->create($line + ['order_id' => $order->getKey()]);
            }

            $this->createParticipants($order, $participants, $quote);

            // The cart is emptied only now. Clearing it earlier would lose the
            // basket if the insert failed, and the 2024 build left a stray
            // 'encours' row that blocked that customer from ordering again.
            $cart->items()->delete();
            $cart->delete();

            return $order->load(['items', 'participants']);
        });
    }

    /**
     * Refuse a registration that would exceed the delegate cap.
     *
     * @throws RuntimeException
     */
    private function assertCapacityAvailable(?Edition $edition, int $requested): void
    {
        $capacity = (int) config('conference.capacity', 300);

        if ($capacity <= 0) {
            return;
        }

        // Only paid orders hold a place. Counting pending ones would let
        // abandoned baskets reserve the venue, which is how a cap gets hit
        // while the seats are still empty.
        $taken = $edition === null
            ? 0
            : (int) Order::query()
                ->where('edition_id', $edition->getKey())
                ->where('status', OrderStatus::Paid->value)
                ->withCount('participants')
                ->get()
                ->sum('participants_count');

        if ($taken + $requested > $capacity) {
            throw new RuntimeException(__('order.capacity_reached', [
                'capacity' => $capacity,
                'taken' => $taken,
            ]));
        }
    }

    /**
     * The named participants must match the places being bought.
     *
     * @param  array<int, array<string, mixed>>  $participants
     *
     * @throws RuntimeException
     */
    private function assertParticipantCount(array $participants, Quote $quote): void
    {
        if (count($participants) !== $quote->participantCount) {
            throw new RuntimeException(__('order.participant_mismatch', [
                'expected' => $quote->participantCount,
                'received' => count($participants),
            ]));
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $participants
     */
    private function createParticipants(Order $order, array $participants, Quote $quote): void
    {
        // Member places are attached to the participants who claimed them.
        // The buyer's ordering is preserved; only the pricing is resolved, and
        // a claim beyond the granted member count falls back to the standard
        // rate rather than being silently dropped.
        $memberRemaining = $quote->memberCount;

        foreach ($participants as $participant) {
            $claimed = (bool) ($participant['is_member'] ?? false);
            $isMember = $claimed && $memberRemaining > 0;

            if ($isMember) {
                $memberRemaining--;
            }

            Participant::query()->create([
                'order_id' => $order->getKey(),
                'full_name' => (string) $participant['full_name'],
                'job_title' => $participant['job_title'] ?? null,
                'email' => $participant['email'] ?? null,
                'phone' => $participant['phone'] ?? null,
                'is_member' => $isMember,
                'badge_name' => $participant['badge_name'] ?? null,
            ]);
        }
    }

    private function numericCurrency(string $currency): string
    {
        $currency = strtoupper($currency);

        return (string) (config("cmi.currencies.{$currency}.numeric")
            ?? config('cmi.currencies.MAD.numeric', '504'));
    }

    /**
     * How many of this line's places may be priced at the member rate.
     *
     * The registrant *declares* how many participants are IIA Maroc members,
     * because one invoice can cover a member and their colleagues. The
     * declaration is capped by what the server can substantiate: the buyer's
     * own active membership. One member therefore cannot buy a whole group at
     * the member rate, which is the gap the 2024 form left open — a hidden
     * `adherent1` checkbox was the only control, and it was trusted.
     */
    private function grantedMemberQuantity(CartItem $item, User $user): int
    {
        $declared = min($item->member_quantity, $item->quantity);

        if ($declared <= 0) {
            return 0;
        }

        return $user->isActiveMember() ? $declared : 0;
    }
}