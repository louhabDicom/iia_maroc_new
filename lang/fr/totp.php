<?php

declare(strict_types=1);

/*
| The authenticator-app enrolment flow, replacing SMS phone verification.
|
| `tradeoff` and `recovery.warning` are the two that matter most and the two most
| often left out. A delegate who does not realise they should have saved the
| codes finds out on the day their phone is flat, and by then this page cannot
| help them.
*/

return [
    'code_hint' => 'Saisissez le code à :digits chiffres affiché par votre application.',
    'code_label' => 'Code de votre application',
    'code_required' => 'Saisissez le code affiché par votre application.',
    'done_heading' => 'Application configurée',
    'done_lede' => 'Votre compte est confirmé. Bienvenue à ARABCIA 2026.',
    'done_title' => 'Application configurée',
    'heading' => 'Configurez votre application d’authentification',
    'invalid_code' => 'Ce code n’est pas correct. Attendez qu’un nouveau code s’affiche, puis réessayez.',
    'lede' => 'Une application d’authentification produit les codes sur votre téléphone. C’est gratuit, illimité, et sans aucun fournisseur de SMS.',

    'not_enrolled' => 'Vous devez configurer votre application d’authentification.',

    'pending_notice' => 'Votre compte est créé. Il reste à configurer votre application d’authentification avant de réserver.',

    'qr_alt' => 'QR code de configuration pour :issuer',

    'recovery' => [
        'already_shown' => 'Ces codes ne s’affichent qu’une fois. Il vous reste :count codes de secours.',
        'continue' => 'Continuer',
        'heading' => 'Codes de secours',
        'lede' => 'Gardez ces codes ailleurs que sur votre téléphone. Chacun ne sert qu’une fois.',
        'remaining' => 'Il vous reste :count codes de secours.',
        'title' => 'Codes de secours',
        'warning' => 'Photographiez-les ou imprimez-les. Si vous perdez votre téléphone et ces codes, seule notre assistance peut rétablir votre compte.',
    ],

    'step_install' => 'Installez une application d’authentification',
    'step_install_lede' => 'Google Authenticator, Microsoft Authenticator ou 1Password sur votre téléphone.',

    'step_manual' => 'Ou saisissez la clé à la main',
    'step_manual_lede' => 'Si vous ne pouvez pas scanner le QR code, saisissez cette clé dans votre application.',

    'step_scan' => 'Scannez le QR code',
    'step_scan_lede' => 'Avec :issuer, votre application affichera un nouveau code toutes les 30 secondes.',

    'submit' => 'Configurer mon compte',
    'title' => 'Configuration de l’authentification',
    'tradeoff' => 'Cette méthode est gratuite, mais elle suppose un téléphone de réserve et ces codes de secours. Sans l’un des deux, votre compte devient irrécupérable sans notre aide.',
];
