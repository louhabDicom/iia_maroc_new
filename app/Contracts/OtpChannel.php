<?php

namespace App\Contracts;

use App\Enums\OtpPurpose;
use App\Models\OtpCode;

/**
 * A channel that can deliver a one-time code.
 *
 * Implementations must not log the code in production, and must throw
 * \App\Exceptions\OtpDeliveryException on failure so the caller can decide
 * whether to surface an error or fall back to another channel.
 */
interface OtpChannel
{
    /**
     * Send a code. `$code` is the plaintext, available only at send time.
     *
     * @return string provider message id, for support and dispute lookups
     *
     * @throws \App\Exceptions\OtpDeliveryException
     */
    public function send(string $identifier, string $code, OtpPurpose $purpose, string $locale): string;

    /**
     * Whether this channel can currently deliver to the given identifier.
     */
    public function supports(string $identifier): bool;

    /**
     * What the `identifier` column holds for this channel.
     *
     * This decides how the identifier is canonicalised before it is stored, so
     * a phone number and a Telegram chat id are never conflated. Constants:
     * `phone`, `chat_id`, `email`.
     */
    public function identifierType(): string;

    /**
     * Short name recorded on the otp_codes row.
     */
    public function name(): string;
}
