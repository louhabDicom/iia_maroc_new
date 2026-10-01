<?php

namespace App\Services\Otp;

use App\Contracts\OtpChannel;
use App\Enums\OtpDriver;
use App\Exceptions\OtpDeliveryException;
use App\Services\Security\SecurityEventLogger;
use Illuminate\Support\Str;

/**
 * Base class for OTP delivery channels.
 *
 * Provides the shared safety rules so each concrete channel cannot forget them:
 *
 *  1. Refuse to run in production when the driver is `log`.
 *  2. Never put a code in an exception message or a log line.
 *  3. Normalise the identifier (phone) before sending.
 */
abstract class AbstractOtpChannel implements OtpChannel
{
    public function __construct(
        protected readonly SecurityEventLogger $events,
    ) {}

    /**
     * @throws OtpDeliveryException
     */
    public function send(string $identifier, string $code, \App\Enums\OtpPurpose $purpose, string $locale): string
    {
        $identifier = $this->normalise($identifier);

        if (! $this->supports($identifier)) {
            throw new OtpDeliveryException(
                "The [{$this->name()}] channel cannot deliver to the supplied identifier."
            );
        }

        // Structural safety net: the log driver must never be reachable in
        // production, or anyone could read another user's code from the log.
        if ($this instanceof LogOtpChannel && app()->isProduction()) {
            throw new OtpDeliveryException(
                'The log OTP driver is disabled in production. Configure a real channel.'
            );
        }

        return $this->dispatch($identifier, $code, $purpose, $locale);
    }

    /**
     * @throws OtpDeliveryException
     */
    abstract protected function dispatch(
        string $identifier,
        string $code,
        \App\Enums\OtpPurpose $purpose,
        string $locale,
    ): string;

    /** Phone numbers are stored and compared in a single canonical form. */
    protected function normalise(string $identifier): string
    {
        if ($this->identifierType() === 'phone') {
            return PhoneNumber::normalise($identifier);
        }

        return trim($identifier);
    }

    /**
     * Build the human-facing message. Implementations must not include the code
     * in anything they log.
     */
    protected function message(string $code, \App\Enums\OtpPurpose $purpose, string $locale): string
    {
        return __('otp.message', [
            'code' => $code,
            'minutes' => (int) ceil($purpose->ttlSeconds() / 60),
            'app' => config('app.name'),
        ], $locale);
    }

    /**
     * Mask an identifier for logs and audit rows.
     *
     * Delegates to PhoneNumber so a number is masked identically in the
     * channel, the audit row and the admin view.
     */
    protected function mask(string $identifier): string
    {
        return PhoneNumber::mask($identifier);
    }
}
