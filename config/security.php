<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Rate limits
    |--------------------------------------------------------------------------
    |
    | Declared here rather than inline in controllers so a limit cannot drift
    | between the form request and the controller that enforces it. OTP limits
    | live in config/otp.php, next to the delivery policy they protect.
    |
    | Each value is "attempts within the decay window". The per-IP ceiling is
    | derived as a multiple of the per-identifier limit in
    | App\Services\Security\RateLimiter, because a shared NAT or a corporate
    | proxy puts many legitimate users behind one address.
    |
    */

    'rate_limit_login' => (int) env('RATE_LIMIT_LOGIN', 5),
    'login_decay_seconds' => (int) env('RATE_LIMIT_LOGIN_DECAY', 300),

    'rate_limit_contact' => (int) env('RATE_LIMIT_CONTACT', 3),
    'contact_decay_seconds' => (int) env('RATE_LIMIT_CONTACT_DECAY', 3600),

    /*
    |--------------------------------------------------------------------------
    | Account lifecycle
    |--------------------------------------------------------------------------
    |
    | An account created but never verified is dead weight: it holds a unique
    | phone number and an email address that the real person can then not reuse.
    | `prune_after_days` defines how long that state is tolerated before the
    | cleanup command anonymises it.
    |
    */

    'prune_incomplete_after_days' => (int) env('PRUNE_INCOMPLETE_AFTER_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Session hardening
    |--------------------------------------------------------------------------
    |
    | `regenerate_on_privilege_change` is the reason the sign-in and verification
    | paths call session()->regenerate(): without it, a session id observed
    | before authentication stays valid afterwards.
    |
    */

    'regenerate_on_privilege_change' => true,

    'idle_timeout_minutes' => (int) env('SESSION_IDLE_TIMEOUT', 120),

];
