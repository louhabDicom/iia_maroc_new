<?php

return [
    'title'   => 'Configurer l’application d’authentification',
    'heading' => 'Activez la double authentification',
    'lede'    => 'À chaque connexion, vous saisirez un code temporaire affiché sur votre téléphone. Votre compte reste protégé même si votre mot de passe est dévoilé.',

    'pending_notice' => 'Votre compte est créé, mais la protection par application n’est pas encore active. Vous pouvez fermer cette page et revenir plus tard : rien n’est perdu.',

    // Step 1
    'step_install'      => 'Installez une application d’authentification',
    'step_install_lede' => 'Toute application compatible TOTP convient. Voici quelques exemples gratuits :',
    'apps_label'        => 'Applications compatibles',
    'apps_note'         => 'Vous en avez déjà une ? Utilisez-la : il n’y a rien à installer de plus.',

    // Step 2
    'step_scan'      => 'Scannez le QR code',
    'step_scan_lede' => 'Ouvrez l’application, choisissez « Ajouter un compte », puis visez le code ci-dessous. Le compte apparaîtra sous le nom « :issuer ».',
    'qr_alt'         => 'QR code pour ajouter le compte :issuer à votre application d’authentification',

    // Step 3
    'step_manual'      => 'Ou saisissez la clé à la main',
    'step_manual_lede' => 'Si le scan est impossible, choisissez « Saisir une clé de configuration » dans l’application et entrez la clé ci-dessous. C’est le même secret que celui du QR code.',
    'key_label'        => 'Clé de configuration',
    'copy'             => 'Copier la clé',
    'copied'           => 'Clé copiée',
    'copy_failed'      => 'Copie impossible : sélectionnez la clé et copiez-la manuellement.',

    // Step 4
    'step_confirm' => 'Saisissez le code pour confirmer',
    'code_label'   => 'Code de vérification',
    'code_hint'    => 'Saisissez les :digits chiffres actuellement affichés dans l’application.',
    'submit'       => 'Activer la double authentification',

    'tradeoff' => 'Cette méthode est gratuite et illimitée, mais si vous perdez votre téléphone sans avoir conservé vos codes de récupération, vous ne pourrez plus vous connecter.',
];