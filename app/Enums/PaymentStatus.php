<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Authorised = 'authorised';   // PreAuth held by the issuer
    case Captured = 'captured';       // funds taken
    case Settled = 'settled';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case Disputed = 'disputed';

    public function isSuccessful(): bool
    {
        return in_array($this, [
            self::Authorised, self::Captured, self::Settled,
        ], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Captured, self::Settled, self::Failed,
            self::Cancelled, self::Refunded,
        ], true);
    }
}
