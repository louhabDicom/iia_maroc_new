<?php

namespace App\Enums;

enum MembershipStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(string $locale = 'fr'): string
    {
        return __("membership.status.{$this->value}", [], $locale);
    }

    public function grantsMemberRate(): bool
    {
        return $this === self::Active;
    }
}
