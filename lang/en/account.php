<?php

declare(strict_types=1);

return [
    'orders' => 'My registrations',
    'totp_not_enrolled' => 'App not set up',
    'totp_enrolled' => 'App set up',
    'totp_label' => 'Authenticator app',
    'profile' => 'Profile',
    'title' => 'My account',

    // The confirmation state of the email address, next to the address itself.
    // It belongs in the account namespace rather than in the TOTP file: it is
    // about the email, it just happens to be read on the same page.
    'email_confirmed' => 'This address is confirmed.',
    'email_pending' => 'This address is not confirmed yet. Confirm it from the link we sent you.',

    // --- Editing the profile -------------------------------------------------
    //
    // 'profile' above is the label on the link *to* this page; the keys below
    // are the page itself.

    'edit_title' => 'Edit my profile',
    'edit_lede' => 'Your details appear on your badge and on the invoice. Keep them current.',
    'edit_details' => 'My details',
    'edit_details_lede' => 'These details identify you on site and on every invoice issued for your registrations.',
    'edit_save' => 'Save my details',

    'edit_password' => 'Password',
    'edit_password_lede' => 'Choose something you do not use anywhere else. You will stay signed in on this device.',
    'current_password' => 'Current password',
    'new_password' => 'New password',
    'confirm_password' => 'Confirm the new password',
    'password_submit' => 'Change my password',
    'current_password_wrong' => 'That is not your current password.',
    'password_same' => 'The new password must be different from the current one.',

    'profile_saved' => 'Your details have been saved.',
    'password_saved' => 'Your password has been changed.',
    'email_reverify' => 'We have sent you a link to confirm your new email address. Your registrations are unaffected.',
];
