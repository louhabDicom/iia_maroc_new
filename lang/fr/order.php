<?php

declare(strict_types=1);

/**
 * Order lifecycle labels.
 *
 * `OrderStatus::label()` reads `order.status.{value}`, and the view calls that
 * method rather than interpolating the key itself, so a new enum case without a
 * label here is visible in one place instead of leaking a raw key into a
 * customer's order history.
 *
 * French and Arabic are gendered for "inscription" (feminine), which is why
 * these read as adjectives agreeing with it rather than as bare participles.
 */
return [
    'status.awaiting_payment' => 'Paiement en cours',
    'status.cancelled'        => 'Annulée',
    'status.expired'          => 'Expirée',
    'status.failed'           => 'Échec du paiement',
    'status.paid'             => 'Payée',
    'status.partially_refunded' => 'Partiellement remboursée',
    'status.pending'          => 'En attente',
    'status.refunded'         => 'Remboursée',

    // Column and form labels for the account and admin screens.
    'reference'   => 'Référence',
    'placed_on'   => 'Passée le',
    'amount'      => 'Montant',
    'no_orders'   => 'Aucune inscription pour le moment.',
    'pay_now'     => 'Payer maintenant',
    'download_invoice' => 'Télécharger la facture',
];
