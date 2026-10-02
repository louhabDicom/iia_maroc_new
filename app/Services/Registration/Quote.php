<?php

declare(strict_types=1);

namespace App\Services\Registration;

use App\Models\TicketType;

/**
 * An authoritative price for a cart.
 *
 * Returned by CheckoutService::quote() before the order exists, so the buyer
 * sees the real figure — member rate included — rather than the cart's
 * estimate. Immutable: it is a value, not a model.
 */
final class Quote
{
    /**
     * @param  array<int, array{
     *     ticket_type_id: int,
     *     label: mixed,
     *     unit_price_member: int,
     *     unit_price_standard: int,
     *     member_quantity: int,
     *     standard_quantity: int,
     *     line_total: int
     * }>  $lines
     */
    public function __construct(
        public readonly array $lines,
        public readonly int $subtotal,
        public readonly int $total,
        public readonly string $currency,
        public readonly int $memberCount,
        public readonly int $standardCount,
        public readonly int $participantCount,
        public readonly ?TicketType $ticketType,
    ) {}

    /**
     * @param  int|null  $minorUnits
     */
    public function formatted(?int $minorUnits = null): string
    {
        return \App\Support\Money::format($minorUnits ?? $this->total, $this->currency);
    }
}