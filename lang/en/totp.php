<?php

return [
    'title' => 'Set up your authenticator app',
    'heading' => 'Turn on two-factor authentication',
    'lede' => 'Each time you sign in, you will enter a temporary code shown on your phone. Your account stays protected even if your password is exposed.',

    'pending_notice' => 'Your account is created, but app protection is not active yet. You can close this page and come back later: nothing is lost.',

    // Step 1
    'step_install' => 'Install an authenticator app',
    'step_install_lede' => 'Any TOTP-compatible app works. Here are a few free examples:',
    'apps_label' => 'Compatible apps',
    'apps_note' => 'Already have one? Use it: there is nothing more to install.',

    // Step 2
    'step_scan' => 'Scan the QR code',
    'step_scan_lede' => 'Open the app, choose “Add account”, then point the camera at the code below. The account will appear as “:issuer”.',
    'qr_alt' => 'QR code to add the :issuer account to your authenticator app',

    // Step 3
    'step_manual' => 'Or type the key by hand',
    'step_manual_lede' => 'If scanning is not possible, choose “Enter a setup key” in the app and type the key below. It is the same secret as the QR code.',
    'key_label' => 'Setup key',
    'copy' => 'Copy key',
    'copied' => 'Key copied',
    'copy_failed' => 'Could not copy: select the key and copy it manually.',

    // Step 4
    'step_confirm' => 'Enter the code to confirm',
    'code_label' => 'Verification code',
    'code_hint' => 'Enter the :digits digits currently shown in the app.',
    'submit' => 'Activate two-factor authentication',
    'help_caption' => 'The code renews regularly: enter the one shown at the moment you confirm.',

    'tradeoff' => 'This method is free and unlimited, but if you lose your phone without keeping your recovery codes, you will not be able to sign in.',
];
