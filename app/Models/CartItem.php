<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A line in a cart.
 *
 * The registrant declares how many of the quantity are IIA Maroc members, which
 * is the only pricing input the buyer supplies. Membership itself is *not*
 * trusted from the request: the member price is granted only for participants
 * matched to an active Membership record at checkout.
 */
class CartItem extends Model
{
    protected $fillable = ['cart_id', 'ticket_type_id', 'quantity', 'member_quantity'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'member_quantity' => 'integer',
        ];
    }

    /** @return BelongsTo<Cart, $this> */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /** @return BelongsTo<TicketType, $this> */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function standardQuantity(): int
    {
        return max(0, $this->quantity - $this->member_quantity);
    }

    /**
     * Indicative total, using the ticket's current prices.
     *
     * Indicative because it cannot know which participants are members until
     * they are matched; the authoritative figure is the order snapshot.
     */
    public function estimatedTotal(): int
    {
        $ticket = $this->ticketType;

        if (! $ticket) {
            return 0;
        }

        return ($ticket->price_standard * $this->standardQuantity())
            + ($ticket->price_member * $this->member_quantity);
    }

    public function formattedEstimatedTotal(): string
    {
        return Money::format($this->estimatedTotal(), $this->ticketType?->currency ?? 'MAD');
    }
}
