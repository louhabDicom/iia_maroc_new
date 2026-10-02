<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | HTTPS
    |--------------------------------------------------------------------------
    |
    | Forces every generated URL to https. Required behind a reverse proxy:
    | otherwise the scheme is taken from the incoming request and Laravel emits
    | http:// links, which browsers then refuse to carry a secure session cookie
    | over. `trust_proxies` must list the real proxy hops, otherwise Request::ip()
    | returns the proxy address and every per-IP rate limit collapses into one
    | shared bucket.
    |
    */

    'force_https' => (bool) env('APP_FORCE_HTTPS', false),

    'trusted_proxies' => env('APP_TRUSTED_PROXIES'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Africa/Casablanca, not Africa/El_Aweine: Morocco keeps UTC+1 all year and
    | shifts to UTC+0 during Ramadan, and only the IANA Casablanca zone encodes
    | that transition. Using a fixed offset would print the wrong time on the
    | programme pages for part of every year.
    |
    | Set APP_TIMEZONE=UTC to store everything in UTC and convert for display.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'Africa/Casablanca'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | Arabic is the default language of this site, and not by preference: the
    | conference is convened by an Arab confederation, hosted in the Kingdom of
    | Morocco, and the primary audience reads Arabic. French and English are
    | served at their own prefixed addresses, so a French or English reader
    | still gets a real URL rather than a negotiated one.
    |
    | `fallback_locale` is Arabic too. It is what a missing key resolves to, and
    | a gap in a translation file should read in the site's own language rather
    | than leak a third one.
    |
    */

    'locale' => env('APP_LOCALE', 'ar'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'ar'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'fr_FR'),

    /*
    |--------------------------------------------------------------------------
    | Available Locales
    |--------------------------------------------------------------------------
    |
    | The 2026 brief requires three languages, and Arabic is not a variant of
    | English: it needs its own translation directory and `dir=rtl` on <html>,
    | which is why `ar` cannot be expressed as a fallback locale.
    |
    | Arabic is listed first because this order is the switcher's order, and the
    | switcher lists the default language first.
    |
    */

    'available_locales' => ['ar', 'fr', 'en'],

    'default_locale' => env('APP_LOCALE', 'ar'),

    /*
    |--------------------------------------------------------------------------
    | Right-to-Left Locales
    |--------------------------------------------------------------------------
    */

    'rtl_locales' => ['ar'],

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache", "array"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
