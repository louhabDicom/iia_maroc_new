<?php

declare(strict_types=1);

return [

    /*
    |---------------------------------------------------------------------------
    | Active driver
    |---------------------------------------------------------------------------
    |
    | "log"      writes the code to the application log. Development and CI only;
    |            AbstractOtpChannel refuses it when APP_ENV=production.
    | "sms"      gateway defined by the `sms` block below. Every SMS provider
    |            meters messages, so this costs money in production.
    | "telegram" Bot API. Free and effectively unlimited for a 300-delegate event,
    |            but the user must have started the bot once. See TelegramLinkController.
    |
    */

    'driver' => env('OTP_DRIVER', 'log'),

    /*
    |---------------------------------------------------------------------------
    | Code policy
    |---------------------------------------------------------------------------
    |
    | length                digits in a code
    | ttl_seconds           lifetime of a registration/login code; sensitive
    |                       purposes (password_reset, phone_change) are pinned to
    |                       300s in App\Enums\OtpPurpose regardless of this value
    | max_attempts          wrong guesses before a code is locked
    | resend_cooldown_seconds  minimum gap between two sends to the same phone
    | max_sends_per_hour    per-identifier hourly send cap (bill-bomb protection)
    | rate_limit_per_phone  verification attempts per 5 minutes
    |
    */

    'length' => (int) env('OTP_LENGTH', 6),

    'ttl_seconds' => (int) env('OTP_TTL_SECONDS', 600),

    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),

    'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', 60),

    'max_sends_per_hour' => (int) env('OTP_MAX_SENDS_PER_HOUR', 5),

    'rate_limit_per_phone' => (int) env('OTP_RATE_LIMIT_PER_PHONE', 5),

    /*
    |---------------------------------------------------------------------------
    | SMS gateway
    |---------------------------------------------------------------------------
    |
    | driver: twilio | vonage | infobip | generic
    |
    | "generic" posts {to, from, text} as JSON to base_url.'/sms' with a bearer
    | token, which suits a local Kannel, Jasmin or SMPP bridge. That is often the
    | cheapest option for Moroccan operators.
    |
    */

    'sms' => [
        'driver' => env('OTP_SMS_DRIVER', 'twilio'),

        'from' => env('OTP_SMS_FROM'),

        'sid' => env('OTP_SMS_SID'),
        'token' => env('OTP_SMS_TOKEN'),

        'api_key' => env('OTP_SMS_API_KEY'),
        'base_url' => env('OTP_SMS_BASE_URL'),
    ],

    /*
    |---------------------------------------------------------------------------
    | Telegram
    |---------------------------------------------------------------------------
    */

    'telegram' => [
        'bot_token' => env('OTP_TELEGRAM_BOT_TOKEN'),
        'api_base' => env('OTP_TELEGRAM_API_BASE', 'https://api.telegram.org'),
        // Must match the secret_token sent to setWebhook, so a third party
        // cannot POST forged updates to the webhook endpoint.
        'webhook_secret' => env('OTP_TELEGRAM_WEBHOOK_SECRET'),
    ],

    /*
    |---------------------------------------------------------------------------
    | Delivery policy
    |---------------------------------------------------------------------------
    |
    | mask_in_response: never echo a code back in an HTTP response, even in local
    | development, so an endpoint can never leak one through a JSON payload.
    |
    */

    'mask_in_response' => true,

];
