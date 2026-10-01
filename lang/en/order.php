<?php

declare(strict_types=1);

/**
 * Order lifecycle labels. See lang/fr/order.php for the rationale; the statuses
 * are shared but the surrounding column labels are not, so a French string is
 * not simply reused here.
 */
return [
    'status.awaiting_payment' => 'Awaiting payment',
    'status.cancelled'        => 'Cancelled',
    'status.expired'          => 'Expired',
    'status.failed'           => 'Payment failed',
    'status.paid'             => 'Paid',
    'status.partially_refunded' => 'Partially refunded',
    'status.pending'          => 'Pending',
    'status.refunded'         => 'Refunded',

    'reference'   => 'Reference',
    'placed_on'   => 'Placed on',
    'amount'      => 'Amount',
    'no_orders'   => 'No registrations yet.',
    'pay_now'     => 'Pay now',
    'download_invoice' => 'Download invoice',
];
