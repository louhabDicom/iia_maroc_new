<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Cart
    |--------------------------------------------------------------------------
    |
    | A basket is session-scoped and expires. Expiry is the fix for the 2024
    | behaviour where a single leftover 'encours' row in `iia_panier` blocked
    | that person from ever ordering again.
    |
    */

    'ttl_minutes' => (int) env('CART_TTL_MINUTES', 120),

    /*
    |--------------------------------------------------------------------------
    | Quotas
    |--------------------------------------------------------------------------
    |
    | The 2026 brief caps the event at 300 delegates, so sales must stop when
    | the room is full rather than when someone remembers to close the form.
    | `capacity` counts paid participants, not orders.
    |
    */

    'capacity' => (int) env('CONFERENCE_CAPACITY', 300),

    /*
    |--------------------------------------------------------------------------
    | Pricing
    |--------------------------------------------------------------------------
    |
    | Currencies come from the ticket type. The member rate is granted only for
    | a participant matched to an active Membership record, never because the
    | form said so.
    |
    */

    'default_currency' => env('CONFERENCE_CURRENCY', 'MAD'),

    'currencies' => ['MAD', 'USD'],

    /*
    |--------------------------------------------------------------------------
    | Feature switches
    |--------------------------------------------------------------------------
    |
    | speaker_submissions_open is false at launch: the brief defers the public
    | call-for-speakers and presentation-upload forms to the end of the
    | conference. Off means the route 404s, not that it silently accepts input.
    |
    */

    'speaker_submissions_open' => (bool) env('SPEAKER_SUBMISSIONS_OPEN', false),

    'presentation_uploads_open' => (bool) env('PRESENTATION_UPLOADS_OPEN', false),

];
