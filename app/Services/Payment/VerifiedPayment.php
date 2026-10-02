<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\PaymentStatus;

/**
 * The result of verifying a gateway payload.
 *
 * A value object rather than a boolean, because the checkout needs four
 * separate answers and collapsing them into one flag is how "the signature was
 * valid but the bank declined" turns into a paid order.
 */
final class VerifiedPayment
{
    public function __construct(
        public readonly PaymentStatus $status,
        public readonly ?string $transactionId = null,
        public readonly ?string $reference = null,
        public readonly ?string $returnCode = null,
        public readonly ?string $maskedPan = null,
        public readonly ?string $cardBrand = null,
        public readonly ?string $cardIssuer = null,
        public readonly ?string $authCode = null,
        public readonly ?int $amount = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->status->isSuccessful();
    }

    /**
     * CMI's own success code.
     *
     * `ProcReturnCode = 00` is the only value that means "authorised". Every
     * other value, including an absent one, is a failure — which is why the
     * comparison is strict and the default is a failure rather than a
     * success. The 2024 callback wrote `if ($_POST["ProcReturnCode"] == "00")`
     * on an unguarded key, so a callback that omitted the field produced a PHP
     * notice and an empty-string comparison that behaved unpredictably.
     */
    public static function authorised(
        ?string $returnCode = '00',
        ?string $transactionId = null,
        ?string $reference = null,
        ?string $maskedPan = null,
        ?string $cardBrand = null,
        ?string $cardIssuer = null,
        ?string $authCode = null,
        ?int $amount = null,
    ): self {
        return new self(
            status: PaymentStatus::Authorised,
            transactionId: $transactionId,
            reference: $reference,
            returnCode: $returnCode,
            maskedPan: $maskedPan,
            cardBrand: $cardBrand,
            cardIssuer: $cardIssuer,
            authCode: $authCode,
            amount: $amount,
        );
    }

    public static function failed(
        string $returnCode,
        ?string $message = null,
        ?string $reference = null,
        ?string $transactionId = null,
    ): self {
        return new self(
            status: PaymentStatus::Failed,
            transactionId: $transactionId,
            // The reference is carried on a failure too, and omitting it here
            // is how a declined payment becomes unattributable: the settlement
            // service resolves the order from this value, so a failure with no
            // reference cannot be recorded against the order it belongs to and
            // that order waits forever in `pending`, looking abandoned.
            reference: $reference,
            returnCode: $returnCode,
            errorMessage: $message,
        );
    }
}