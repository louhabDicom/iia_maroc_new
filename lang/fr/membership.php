<?php

declare(strict_types=1);

/**
 * IIA Maroc membership status labels.
 *
 * `MembershipStatus::label()` reads `membership.status.{value}`.
 *
 * Only `active` unlocks the member rate; the other three exist so the account
 * page can explain *why* a member is still being charged the standard price,
 * which the 2024 build never did — it simply charged the higher amount.
 */
return [
    'status.active'    => 'Adhésion active',
    'status.cancelled' => 'Adhésion résiliée',
    'status.pending'   => 'Adhésion en cours de traitement',
    'status.rejected'  => 'Adhésion refusée',

    'heading'     => 'Adhésion IIA Maroc',
    'member_rate' => 'Tarif adhérent appliqué',
    'standard_rate_pending' => 'Tarif standard appliqué : votre adhésion n\'est pas encore active.',
];
