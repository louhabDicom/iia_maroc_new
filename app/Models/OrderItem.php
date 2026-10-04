<?php

namespace App\Models;

use App\Casts\TranslatedString;
use App\Support\Money;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Price snapshot for one line of an order.
 *
 * The unit prices are copied from the ticket type at checkout so that changing
 * `ticket_types.price_member` next year can never alter an already-issued
 * invoice. This was the core defect in the 2024 build, where `facture.php`
 * re-joined `iia_offre` and re-read the live price.
 */
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id', 'ticket_type_id', 'label',
        'unit_price_member', 'unit_price_standard',
        'member_quantity', 'standard_quantity', 'line_total',
    ];

    protected function casts(): array
    {
        return [
            'label' => TranslatedString::class,
            'unit_price_member' => 'integer',
            'unit_price_standard' => 'integer',
            'member_quantity' => 'integer',
            'standard_quantity' => 'integer',
            'line_total' => 'integer',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<TicketType, $this> */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function totalQuantity(): int
    {
        return $this->member_quantity + $this->standard_quantity;
    }

    public function formattedLineTotal(string $currency = 'MAD'): string
    {
        return Money::format($this->line_total, $currency);
    }

    /**
     * The price of one member seat on this line.
     *
     * The invoice lists a row per attendee, so it has to price a single seat
     * rather than repeat the line total. Both unit prices are frozen at
     * checkout for the same reason `line_total` is: a rate read from the live
     * ticket type would let a printed invoice disagree with what was charged.
     */
    public function formattedUnitPriceMember(string $currency = 'MAD'): string
    {
        return Money::format($this->unit_price_member, $currency);
    }

    public function formattedUnitPriceStandard(string $currency = 'MAD'): string
    {
        return Money::format($this->unit_price_standard, $currency);
    }

    /**
     * The rate a single seat on this line was sold at.
     *
     * A member seat is cheaper, so the choice follows the line's own quantities
     * rather than a flag passed in from the view.
     */
    public function formattedSeatPrice(string $currency = 'MAD'): string
    {
        return $this->member_quantity > 0
            ? $this->formattedUnitPriceMember($currency)
            : $this->formattedUnitPriceStandard($currency);
    }
}
