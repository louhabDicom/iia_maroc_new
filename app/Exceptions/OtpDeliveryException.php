<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when an OTP could not be handed to its delivery channel.
 *
 * Distinct from a verification failure: the code may be perfectly valid, we
 * just could not tell the user about it.
 */
class OtpDeliveryException extends RuntimeException
{
    public function __construct(
        string $message = 'The verification code could not be delivered.',
        public readonly ?string $providerMessageId = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function driverMisconfigured(string $driver, string $missing): self
    {
        return new self("OTP driver [{$driver}] is missing required configuration: {$missing}.");
    }
}
