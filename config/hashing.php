<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | The application uses bcrypt for every new password, but the 2024
    | WordPress import carries phpass ($P$) hashes that bcrypt cannot read.
    | The default driver is therefore a bcrypt hasher that also understands
    | phpass, registered as "legacy" in AppServiceProvider. It hashes new
    | passwords with bcrypt exactly as before.
    |
    | Supported: "legacy", "bcrypt", "argon", "argon2id"
    |
    */

    'driver' => env('HASH_DRIVER', 'legacy'),

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => env('HASH_VERIFY', true),
        'limit' => env('BCRYPT_LIMIT', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon Options
    |--------------------------------------------------------------------------
    */

    'argon' => [
        'memory' => env('ARGON_MEMORY', 65536),
        'threads' => env('ARGON_THREADS', 1),
        'time' => env('ARGON_TIME', 4),
        'verify' => env('HASH_VERIFY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rehash On Login
    |--------------------------------------------------------------------------
    */

    'rehash_on_login' => true,

];