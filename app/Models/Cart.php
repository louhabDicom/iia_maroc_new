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
     *
     * Authenticated visitors get a cart keyed on their account, not on the
     * session. Two reasons, and the second is the one that matters:
     *
     *  1. the checkout requires a signed-in account, so a guest's basket has
     *     to survive the login — keying only on the session means a basket
     *     built before signing in is orphaned at the moment it is needed;
     *  2. a basket tied to a session disappears on a new device or a cleared
     *     cookie, so someone who assembled a group order on their phone and
     *     then paid from a laptop arrives at an empty basket with no
     *     explanation.
     *
     * A guest still gets a session-scoped cart, because there is no account to
     * hang it on yet.
     */
    public static function forSession(string $sessionId, ?User $user = null): self
    {
        $query = static::query();

        $query->where(function ($q) use ($sessionId, $user): void {
            $q->where('session_id', $sessionId);

            if ($user !== null) {
                // An account's cart is reachable from any session it was last
                // used from, which is what makes it survive a device change.
                $q->orWhere('user_id', $user->getKey());
            }
        })->where('expires_at', '<=', now())->delete();

        $attributes = $user !== null
            ? ['user_id' => $user->getKey()]
            : ['user_id' => null];

        $existing = static::query()
            ->where('expires_at', '>', now())
            ->when(
                $user !== null,
                fn ($q) => $q->where('user_id', $user->getKey()),
                fn ($q) => $q->where('session_id', $sessionId),
            )
            ->first();

        if ($existing !== null) {
            // Keep the session column current so a later guest request on the
            // same session still finds it.
            if ($existing->session_id !== $sessionId || $existing->user_id !== ($user?->getKey())) {
                $existing->forceFill($attributes + ['session_id' => $sessionId])->save();
            }

            return $existing;
        }

        return static::query()->create(
            ['session_id' => $sessionId] + $attributes + [
                'expires_at' => now()->addMinutes((int) config('conference.cart_ttl_minutes', config('cart.ttl_minutes', 120))),
            ]
        );
    }
}
