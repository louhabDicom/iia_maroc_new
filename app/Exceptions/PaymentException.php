<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A payment could not be accepted.
 *
 * A dedicated exception rather than a boolean return, because every caller
 * has the same obligation when a payment cannot be trusted: stop, do not mark
 * the order paid, and record what happened. A boolean makes that a decision
 * each call site can get wrong, and the wrong decision is either giving away
 * a place or refusing a customer who has paid.
 */
class PaymentException extends RuntimeException
{
    public static function invalidSignature(): self
    {
        return new self('The payment gateway response signature is invalid.');
    }

    public static function amountMismatch(int $expected, int $received): self
    {
        return new self(sprintf(
            'The gateway amount (%d) does not match the order total (%d).',
            $received,
            $expected,
        ));
    }

    public static function unresolvableOrder(?string $reference): self
    {
        return new self(sprintf(
            'No order matches the gateway reference %s.',
            $reference === null ? '(absent)' : $reference,
        ));
    }
}