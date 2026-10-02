<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Order;

/**
 * A payment gateway.
 *
 * The checkout talks only to this, so the real CMI driver and the local test
 * driver are interchangeable and the whole flow — order creation, redirect,
 * server-to-server callback, client return, reconciliation — is rehearsable
 * without a card. That is the point: the 2024 build could only be tested by
 * moving real money, which is why it shipped with a payment path nobody had
 * ever seen succeed.
 */
interface PaymentGateway
{
    /**
     * Prepare a payment attempt and return where to send the customer.
     *
     * @return array{gateway_url: string, fields: array<string, string>, method: string}
     */
    public function redirectFor(Order $order, string $returnUrl, string $cancelUrl): array;

    /**
     * Validate an inbound callback/return payload.
     *
     * Must throw rather than return false on an invalid signature: a caller
     * that treats "could not verify" as "not paid" will happily show a
     * customer a paid order, and one that treats it as "ignore" will ship
     * goods for free.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verify(array $payload): VerifiedPayment;

    /**
     * The merchant's reference the gateway echoes back, resolved to an order.
     *
     * Returning null is legitimate: an `oid` we never issued is a probe, not
     * an error, and must not be turned into an order.
     *
     * @param  array<string, mixed>  $payload
     */
    public function resolveOrder(array $payload): ?Order;
}