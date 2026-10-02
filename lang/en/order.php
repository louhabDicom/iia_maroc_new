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
    'address'     => 'Address',
    'amount'      => 'Amount',
    'no_orders'   => 'No registrations yet.',
    'pay_now'     => 'Pay now',
    'download_invoice' => 'Download invoice',

    // --- Checkout ---------------------------------------------------------
    'title'           => 'Conference registration',
    'ticket'          => 'Ticket',
    'quantity'        => 'Quantity',
    'member_places'   => 'Member places',
    'member_places_exceed' => 'Member places cannot exceed the total number of places.',
    'participants'    => 'Participants',
    'participant_name' => 'Full name',
    'participant_number' => 'Participant :number',
    'participant_help' => 'One entry per reserved place. The member rate applied is the one your active membership entitles you to.',
    'items'      => 'Order details',
    'total'      => 'Total',
    'member_saving_applied' => 'The member rate has been applied to :count place(s).',
    'participant_count_mismatch' => 'You reserved :expected places, but :count participant forms were submitted.',
    'participant_mismatch' => 'The number of participants (:received) does not match the number of places reserved (:expected).',
    'billing_details' => 'Billing details',
    'place_order'     => 'Confirm and pay',
    'email_receipt'   => 'A confirmation e-mail will be sent to you.',
    'summary'         => 'Summary',
    'status'          => 'Status',
    'sign_in_to_checkout' => 'Sign in to complete your registration.',
    'verify_to_checkout'  => 'Verify your phone number before completing your registration.',
    'not_payable'         => 'This order can no longer be paid.',
    'capacity_reached'    => 'The venue is full (:capacity places, :taken already taken). Please contact us to join the waiting list.',

    // --- Cart -------------------------------------------------------------
    'orders'          => 'My orders',
    'cart_empty'   => 'Your basket is empty.',
    'cart_updated' => 'Your basket has been updated.',
    'cart_emptied' => 'Your basket has been emptied.',

    'cart' => [
        'add'           => 'Add to basket',
        'title'         => 'My basket',
        'subtitle'      => 'Review the places you wish to reserve before continuing.',
        'estimate'      => 'Estimated total',
        'estimate_note' => 'This figure is indicative. The final total, which takes your membership status into account, is calculated at the next step.',
        'summary_note'  => 'Participants are named at the next step.',
        'continue'      => 'Continue registering',
        'clear'         => 'Empty the basket',
        'checkout'      => 'Proceed to checkout',
        'sign_in_note'  => 'You will be asked to sign in before payment. Your basket will be kept.',
    ],

    // --- Payment ----------------------------------------------------------
    'payment' => [
        'confirmed'       => 'Payment confirmed. Your invoice is available in your orders.',
        'failed'          => 'The payment did not go through.',
        'generic_failure' => 'The payment was declined.',
        'pending'         => 'Payment awaiting confirmation from the bank.',
        'pending_note'    => 'If you have paid, your registration will be confirmed within moments. This page updates itself.',
        'cancelled'       => 'Payment cancelled.',
        'cancelled_note'  => 'Your order has not been charged. You can resume the payment at any time.',
        'retry'           => 'Resume payment',
        'secure'          => 'Secure payment via CMI.',
        'last_attempt'    => 'Latest payment attempt',
        'test_title'      => 'Payment gateway test page',
        'test_banner'     => 'Test environment — no real payment will be taken.',
        'test_intro'      => 'This page rehearses the complete payment flow without a card.',
        'test_approve'    => 'Simulate an approved payment',
        'test_decline'    => 'Simulate a declined payment',
        'test_note'       => 'Both buttons post a signed payload to the real callback endpoint, so the production code path is genuinely exercised.',
    ],

    'invoice' => [
        'not_available' => 'The invoice is available once the order has been paid.',
        'title'         => 'Invoice no. :number',
        'billed_to'     => 'Billed to',
        'details'       => 'Details',
        'issued_on'     => 'Issue date',
        'method'        => 'Payment method',
        'cmi'           => 'Moroccan cards (CMI)',
        'subtotal'      => 'Subtotal',
        'discount'      => 'Discount',
        'tax'           => 'Tax',
        'rate'          => 'Rate',
        'footer'        => 'Generated by :organiser. Any correction must be requested from the organisers.',
    ],

    // --- Mail -------------------------------------------------------------
    'mail' => [
        'paid_subject'     => 'ARABCIA 2026 — Registration confirmed (:reference)',
        'failed_subject'   => 'ARABCIA 2026 — Payment unsuccessful (:reference)',
        'paid_intro'       => "Hello,\n\nYour registration for the ARABCIA 2026 conference is confirmed. We have recorded your payment.",
        'failed_intro'     => "Hello,\n\nThe payment for your ARABCIA 2026 conference registration did not go through.",
        'invoice_attached' => 'The invoice is attached to this message.',
        'invoice_online'   => 'You can download your invoice from your "My orders" area.',
        'failed_action'    => 'You can resume payment at any time from your "My orders" area.',
        'footer'           => 'Kind regards, the ARABCIA 2026 organisation.',
    ],

    'cancel' => [
        'done'    => 'Your order has been cancelled.',
        'already' => 'This order is already closed.',
        'paid'    => 'This order is paid and cannot be cancelled. Please contact the organisers for a refund.',
        'action'  => 'Cancel the order',
        'confirm' => 'Confirm cancellation of this order?',
    ],
];
