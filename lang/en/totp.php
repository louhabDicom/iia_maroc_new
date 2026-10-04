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
    'code_hint' => 'Enter the :digits-digit code shown by your app.',
    'code_label' => 'Code from your app',
    'code_required' => 'Enter the code shown by your app.',
    'done_heading' => 'App configured',
    'done_lede' => 'Your account is confirmed. Welcome to ARABCIA 2026.',
    'done_title' => 'App configured',
    'heading' => 'Set up your authenticator app',
    'invalid_code' => 'That code is not correct. Wait for a new one to appear, then try again.',
    'lede' => 'An authenticator app produces the codes on your own phone. It is free, unlimited, and involves no SMS provider at all.',

    'not_enrolled' => 'You need to set up your authenticator app.',

    'pending_notice' => 'Your account is created. You still need to set up your authenticator app before you can book.',

    'qr_alt' => 'Setup QR code for :issuer',

    'recovery' => [
        'already_shown' => 'These codes are shown only once. You have :count recovery codes left.',
        'continue' => 'Continue',
        'heading' => 'Recovery codes',
        'lede' => 'Keep these somewhere other than your phone. Each one works only once.',
        'remaining' => 'You have :count recovery codes left.',
        'title' => 'Recovery codes',
        'warning' => 'Photograph or print them. If you lose your phone and these codes, only our support team can restore your account.',
    ],

    'step_install' => 'Install an authenticator app',
    'step_install_lede' => 'Google Authenticator, Microsoft Authenticator or 1Password on your phone.',

    'step_manual' => 'Or type the key by hand',
    'step_manual_lede' => 'If you cannot scan the QR code, enter this key into your app instead.',

    'step_scan' => 'Scan the QR code',
    'step_scan_lede' => 'With :issuer, your app will show a new code every 30 seconds.',

    'submit' => 'Finish setting up',
    'title' => 'Authenticator setup',
    'tradeoff' => 'This method is free, but it assumes a spare handset and these recovery codes. Without one of the two, your account cannot be recovered without our help.',
];
