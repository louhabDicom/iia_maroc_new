<?php

namespace App\Models;

use App\Casts\TranslatedString;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A billable ticket type for one edition.
 *
 * Money is `unsignedBigInteger` minor units. The legacy `iia_offre` stored
 * prices as TEXT ('7500'), which is why the 2024 checkout recomputed totals in
 * PHP floats and could not compare or sum them reliably.
 */
class TicketType extends Model
{
    /** @use HasFactory<\Database\Factories\TicketTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'edition_id', 'code', 'name', 'description', 'includes',
        'price_member', 'price_standard', 'currency', 'currency_numeric',
        'colour', 'max_quantity', 'sort_order', 'is_active',
        'sales_start_at', 'sales_end_at',
    ];

    protected function casts(): array
    {
        return [
            'name' => TranslatedString::class,
            'description' => TranslatedString::class,
            'includes' => \App\Casts\TranslatedList::class,
            'price_member' => 'integer',
            'price_standard' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'max_quantity' => 'integer',
            'sales_start_at' => 'datetime',
            'sales_end_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /**
     * Orders written against this tariff.
     *
     * The inverse of `Order::ticketType()`, and the relation the admin
     * dashboard aggregates over to report places sold per tariff. It is scoped
     * to paid orders rather than to all of them, because "how many places were
     * sold" is a question about money received: a cancelled order holds no
     * places, and counting it would report demand that does not exist.
     *
     * Scoping a relation rather than filtering in the caller keeps that rule in
     * one place — a second caller that forgets the filter silently overstates
     * sales, which is exactly the kind of number nobody audits until it is
     * wrong.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)
            ->where('status', OrderStatus::Paid);
    }

    /** @return HasMany<OrderItem, $this> */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @param  Builder<TicketType>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('sales_start_at')->orWhere('sales_start_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('sales_end_at')->orWhere('sales_end_at', '>=', now()));
    }

    /** Price in minor units for a given membership state. */
    public function priceFor(bool $isMember): int
    {
        return $isMember ? $this->price_member : $this->price_standard;
    }

    /** Minor units -> localised display string, e.g. "7 500,00 MAD". */
    public function formatAmount(int $minorUnits): string
    {
        return \App\Support\Money::format($minorUnits, $this->currency);
    }

    public function priceForLabel(bool $isMember): string
    {
        return $this->formatAmount($this->priceFor($isMember));
    }
}
