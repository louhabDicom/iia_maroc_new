<?php

namespace App\Enums;

/**
 * Order lifecycle.
 *
 * Replaces the legacy free-text `iia_commande_conference.etat_payment` in
 * ('encours', 'valider', 'annuler'). The important addition is `Expired` and
 * the explicit legal transitions: the 2024 site had no way to represent an
 * order abandoned mid-payment, so abandoned carts silently became permanent
 * "encours" rows that blocked the user from re-registering.
 */
enum OrderStatus: string
{
    case Pending = 'pending';           // created, awaiting payment
    case AwaitingPayment = 'awaiting_payment';
    case Paid = 'paid';
    case Failed = 'failed';             // gateway returned an error
    case Cancelled = 'cancelled';       // user or admin cancelled
    case Expired = 'expired';           // payment window elapsed
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';

    public function label(string $locale = 'fr'): string
    {
        return __("order.status.{$this->value}", [], $locale);
    }

    public function colour(): string
    {
        return match ($this) {
            self::Pending, self::AwaitingPayment => 'warning',
            self::Paid => 'success',
            self::Failed, self::Cancelled, self::Expired => 'danger',
            self::Refunded, self::PartiallyRefunded => 'info',
        };
    }

    public function isPayable(): bool
    {
        return in_array($this, [self::Pending, self::AwaitingPayment, self::Failed], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Cancelled, self::Refunded, self::Expired], true);
    }

    public function isSettled(): bool
    {
        return $this === self::Paid;
    }

    /**
     * Legal state machine. Anything not listed here is rejected, which is what
     * stops a replayed gateway callback from flipping a cancelled order back
     * to paid.
     *
     * `Pending -> Failed` is legal because an order can fail before it is ever
     * presented to the gateway: the buyer completes the checkout and the bank
     * declines, and the order may never have left `Pending` if the redirect was
     * interrupted. Without this edge the failure had nowhere to go and the
     * order sat at `Pending` forever, indistinguishable from an abandoned one.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::AwaitingPayment, self::Paid, self::Failed, self::Cancelled, self::Expired],
            self::AwaitingPayment => [self::Paid, self::Failed, self::Cancelled, self::Expired],
            self::Failed => [self::AwaitingPayment, self::Paid, self::Cancelled, self::Expired],
            self::Paid => [self::Refunded, self::PartiallyRefunded],
            self::Cancelled, self::Expired, self::Refunded, self::PartiallyRefunded => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
