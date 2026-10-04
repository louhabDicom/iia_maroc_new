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
    'status.cancelled' => 'Annulée',
    'status.expired' => 'Expirée',
    'status.failed' => 'Échec du paiement',
    'status.paid' => 'Payée',
    'status.partially_refunded' => 'Partiellement remboursée',
    'status.pending' => 'En attente',
    'status.refunded' => 'Remboursée',

    // Column and form labels for the account and admin screens.
    'reference' => 'Référence',
    'placed_on' => 'Passée le',
    'address' => 'Adresse',
    'amount' => 'Montant',
    'no_orders' => 'Aucune inscription pour le moment.',
    'pay_now' => 'Payer maintenant',
    'download_invoice' => 'Télécharger la facture',

    // --- Checkout ---------------------------------------------------------
    'title' => 'Inscription à la conférence',
    'ticket' => 'Tarif',
    'quantity' => 'Quantité',
    'member_places' => 'Places adhérent',
    'member_places_exceed' => 'Le nombre de places adhérent ne peut pas dépasser le nombre de places.',
    'participants' => 'Participants',
    'participant_name' => 'Nom et prénom',
    'participant_number' => 'Participant :number',
    'participant_help' => 'Renseignez une fiche par place réservée. Le tarif adhérent appliqué est celui que votre statut d\'adhérent actif justifie.',
    'items' => 'Détail de la commande',
    'total' => 'Total',
    'member_saving_applied' => 'Le tarif adhérent a été appliqué à :count place(s).',
    'participant_count_mismatch' => 'Vous avez réservé :expected places, mais :count formulaires de participants ont été soumis.',
    'participant_mismatch' => 'Le nombre de participants (:received) ne correspond pas au nombre de places réservées (:expected).',
    'billing_details' => 'Coordonnées de facturation',
    'place_order' => 'Valider et payer',
    'email_receipt' => 'Un e-mail de confirmation vous sera envoyé.',
    'summary' => 'Récapitulatif',
    'status' => 'Statut',
    'sign_in_to_checkout' => 'Connectez-vous pour finaliser votre inscription.',
    'enroll_to_checkout' => 'Configurez votre application d’authentification avant de finaliser votre inscription.',
    'not_payable' => 'Cette commande ne peut plus être payée.',
    'capacity_reached' => 'Le nombre de places est atteint (:capacity places, :taken déjà réservées). Merci de nous contacter pour une inscription sur liste d\'attente.',

    // --- Cart -------------------------------------------------------------
    'orders' => 'Mes commandes',
    'cart_empty' => 'Votre panier est vide.',
    'cart_updated' => 'Votre panier a été mis à jour.',
    'cart_emptied' => 'Votre panier a été vidé.',

    'cart' => [
        'add' => 'Ajouter au panier',
        'title' => 'Mon panier',
        'subtitle' => 'Vérifiez les places que vous souhaitez réserver avant de poursuivre.',
        'estimate' => 'Total indicatif',
        'estimate_note' => 'Ce montant est indicatif. Le total définitif, qui tient compte de votre qualité d\'adhérent, est calculé à l\'étape suivante.',
        'summary_note' => 'Les participants sont renseignés à l\'étape suivante.',
        'continue' => 'Continuer mes inscriptions',
        'clear' => 'Vider le panier',
        'checkout' => 'Passer la commande',
        'sign_in_note' => 'Vous serez invité à vous connecter avant le paiement. Votre panier sera conservé.',
    ],

    // --- Payment ----------------------------------------------------------
    'payment' => [
        'confirmed' => 'Paiement confirmé. Votre facture est disponible dans vos commandes.',
        'failed' => 'Le paiement n\'a pas abouti.',
        'generic_failure' => 'Le paiement a été refusé.',
        'pending' => 'Paiement en attente de confirmation par la banque.',
        'pending_note' => 'Si vous avez payé, votre inscription sera confirmée dans quelques instants. Cette page se met à jour automatiquement.',
        'cancelled' => 'Paiement annulé.',
        'cancelled_note' => 'Votre commande n\'a pas été débitée. Vous pouvez reprendre le paiement à tout moment.',
        'retry' => 'Reprendre le paiement',
        'secure' => 'Paiement sécurisé par CMI.',
        'last_attempt' => 'Dernière tentative de paiement',
        'test_title' => 'Page de test du moyen de paiement',
        'test_banner' => 'Environnement de test — aucun paiement réel ne sera effectué.',
        'test_intro' => 'Cette page permet de rejouer le parcours de paiement complet sans carte bancaire.',
        'test_approve' => 'Simuler un paiement accepté',
        'test_decline' => 'Simuler un paiement refusé',
        'test_note' => 'Les deux boutons envoient une charge signée au vrai point de callback, afin que le chemin de production soit réellement testé.',
    ],

    'invoice' => [
        'not_available' => 'La facture est disponible uniquement après le règlement de la commande.',
        'title' => 'Facture n° :number',
        'billed_to' => 'Facturé à',
        'details' => 'Informations',
        'issued_on' => 'Date d\'émission',
        'method' => 'Mode de paiement',
        'cmi' => 'Cartes marocaines (CMI)',
        'subtotal' => 'Sous-total',
        'discount' => 'Remise',
        'tax' => 'TVA',
        'rate' => 'Tarif',
        'footer' => 'Document généré par :organiser. Toute demande de modification doit être adressée à l\'organisation.',
    ],

    // --- Mail -------------------------------------------------------------
    'mail' => [
        'paid_subject' => 'ARABCIA 2026 — Inscription confirmée (:reference)',
        'failed_subject' => 'ARABCIA 2026 — Paiement non abouti (:reference)',
        'paid_intro' => "Bonjour,\n\nVotre inscription à la conférence ARABCIA 2026 est confirmée. Nous avons bien enregistré votre paiement.",
        'failed_intro' => "Bonjour,\n\nLe paiement de votre inscription à la conférence ARABCIA 2026 n\'a pas abouti.",
        'invoice_attached' => 'La facture est jointe à ce message.',
        'invoice_online' => 'Vous pourrez télécharger votre facture depuis votre espace « Mes commandes ».',
        'failed_action' => 'Vous pouvez reprendre le paiement à tout moment depuis votre espace « Mes commandes ».',
        'footer' => 'Cordialement, l\'organisation ARABCIA 2026.',
    ],

    // --- Cancellation -----------------------------------------------------
    // A paid order is refunded, not cancelled. The wording is explicit
    // because "annulée" on a paid order would suggest the money had gone
    // back when nothing of the kind has happened.
    'cancel' => [
        'done' => 'Votre commande a été annulée.',
        'already' => 'Cette commande est déjà close.',
        'paid' => 'Cette commande est payée et ne peut pas être annulée. Contactez l\'organisation pour un remboursement.',
        'action' => 'Annuler la commande',
        'confirm' => 'Confirmer l\'annulation de cette commande ?',
    ],
];
