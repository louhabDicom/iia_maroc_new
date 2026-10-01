<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One payment attempt against an order.
 *
 * Differences from the legacy `iia_payment` that matter:
 *
 *  - `order_id` is NOT NULL. The 2024 callback wrote its result into
 *    `iia_condidat`, a table the conference flow never populated, so paid
 *    orders stayed "encours" forever. Reconciliation is now a join.
 *  - `idempotency_key` is unique, so a replayed callback is a no-op.
 *  - PAN is never stored; only the gateway's masked form.
 */
class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id', 'driver', 'gateway_transaction_id', 'gateway_reference',
        'idempotency_key', 'status', 'amount', 'currency', 'currency_numeric',
        'masked_pan', 'card_brand', 'card_issuer', 'auth_code', 'return_code',
        'error_message', 'raw_payload',
        'authorised_at', 'captured_at', 'settled_at', 'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'raw_payload' => 'array',
            'authorised_at' => 'datetime',
            'captured_at' => 'datetime',
            'settled_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @param  Builder<Payment>  $query */
    public function scopeSuccessful(Builder $query): void
    {
        $query->whereIn('status', [
            PaymentStatus::Authorised->value,
            PaymentStatus::Captured->value,
            PaymentStatus::Settled->value,
        ]);
    }

    public function formattedAmount(): string
    {
        return Money::format($this->amount, $this->currency);
    }

    /** Human label for a masked PAN, e.g. "Mastercard •••• 2585". */
    public function cardLabel(): string
    {
        if (blank($this->masked_pan)) {
            return '—';
        }

        return trim(($this->card_brand ?? '').' '.$this->masked_pan);
    }
}
