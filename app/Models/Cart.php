<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A session-scoped basket.
 *
 * Deliberately holds no price: prices are resolved from `ticket_types` at
 * checkout, so a cart left open across a price change cannot lock in the old
 * amount. The cart also expires, which is what the 2024 build lacked — a stray
 * 'encours' row in `iia_panier` permanently blocked that person from ordering
 * again, because addToCart.php rejected the duplicate insert.
 */
class Cart extends Model
{
    protected $fillable = ['session_id', 'user_id', 'expires_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<CartItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /** @param  Builder<Cart>  $query */
    public function scopeLive(Builder $query): void
    {
        $query->where('expires_at', '>', now());
    }

    /** @param  Builder<Cart>  $query */
    public function scopeForSession(Builder $query, string $sessionId): void
    {
        $query->where('session_id', $sessionId);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isEmpty(): bool
    {
        return $this->items()->count() === 0;
    }

    public function totalQuantity(): int
    {
        return (int) $this->items()->sum('quantity');
    }

    /**
     * Find or create the live cart for a session, discarding any expired one.
     */
    public static function forSession(string $sessionId, ?User $user = null): self
    {
        static::query()
            ->forSession($sessionId)
            ->where('expires_at', '<=', now())
            ->delete();

        return static::query()->firstOrCreate(
            ['session_id' => $sessionId],
            [
                'user_id' => $user?->getKey(),
                'expires_at' => now()->addMinutes((int) config('cart.ttl_minutes', 120)),
            ],
        );
    }
}
