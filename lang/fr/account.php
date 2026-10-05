<?php

declare(strict_types=1);

return [
    'orders' => 'Mes inscriptions',
    'totp_not_enrolled' => 'Application à configurer',
    'totp_enrolled' => 'Application configurée',
    'totp_label' => 'Application d\'authentification',
    'profile' => 'Profil',
    'title' => 'Mon compte',

    // L'état de confirmation de l'adresse e-mail, affiché à côté de l'adresse.
    // Il relève de `account` et non du fichier TOTP : il concerne l'e-mail, il se
    // trouve simplement sur la même page.
    'email_confirmed' => 'Cette adresse est confirmée.',
    'email_pending' => 'Cette adresse n\'est pas encore confirmée. Confirmez-la depuis le lien qui vous a été envoyé.',

    // --- La page de modification ---------------------------------------------
    //
    // `profile` ci-dessus est l'intitulé du lien *vers* cette page ; les clés
    // ci-dessous sont la page elle-même.

    'edit_title' => 'Modifier mon profil',
    'edit_lede' => 'Vos coordonnées figurent sur votre badge et sur vos factures. Gardez-les à jour.',
    'edit_details' => 'Mes coordonnées',
    'edit_details_lede' => 'Ces informations vous identifient sur place et sur chaque facture émise pour vos inscriptions.',
    'edit_save' => 'Enregistrer mes coordonnées',

    'edit_password' => 'Mot de passe',
    'edit_password_lede' => 'Choisissez un mot de passe que vous n\'utilisez nulle part ailleurs. Vous resterez connecté sur cet appareil.',
    'current_password' => 'Mot de passe actuel',
    'new_password' => 'Nouveau mot de passe',
    'confirm_password' => 'Confirmer le nouveau mot de passe',
    'password_submit' => 'Changer mon mot de passe',
    'current_password_wrong' => 'Ce n\'est pas votre mot de passe actuel.',
    'password_same' => 'Le nouveau mot de passe doit être différent de l\'actuel.',

    'profile_saved' => 'Vos coordonnées ont été enregistrées.',
    'password_saved' => 'Votre mot de passe a été modifié.',
    'email_reverify' => 'Nous vous avons envoyé un lien pour confirmer votre nouvelle adresse e-mail. Vos inscriptions ne sont pas affectées.',
];
