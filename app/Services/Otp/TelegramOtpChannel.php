<?php

namespace App\Services\Otp;

use App\Enums\OtpDriver;
use App\Enums\OtpPurpose;
use App\Exceptions\OtpDeliveryException;
use App\Services\Security\SecurityEventLogger;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Telegram delivery via the Bot API.
 *
 * This is the answer to "a free and unlimited OTP tool". The Bot API has no
 * per-message charge and a practical ceiling around 30 messages/second in
 * bursts, so for a 300-delegate conference it is effectively unlimited at zero
 * cost. Telegram is also very widely used in Morocco.
 *
 * The trade-off is that the user must have started the bot at least once, which
 * is handled by {@see \App\Http\Controllers\Auth\TelegramLinkController}. The
 * identifier for this channel is therefore a Telegram chat id, not a phone
 * number, so the caller must map phone -> chat_id first.
 */
class TelegramOtpChannel extends AbstractOtpChannel
{
    public function name(): string
    {
        return OtpDriver::Telegram->value;
    }

    public function supports(string $identifier): bool
    {
        // Chat ids are numeric and negative for group/supergroup channels.
        return (bool) preg_match('/^-?\d{1,20}$/', trim($identifier));
    }

    public function identifierType(): string
    {
        return 'chat_id';
    }

    protected function dispatch(
        string $identifier,
        string $code,
        OtpPurpose $purpose,
        string $locale,
    ): string {
        $token = config('otp.telegram.bot_token');

        if (blank($token)) {
            throw OtpDeliveryException::driverMisconfigured('telegram', 'OTP_TELEGRAM_BOT_TOKEN');
        }

        $base = rtrim((string) config('otp.telegram.api_base', 'https://api.telegram.org'), '/');

        try {
            $response = Http::timeout(10)
                ->post("{$base}/bot{$token}/sendMessage", [
                    'chat_id' => $identifier,
                    'text' => $this->message($code, $purpose, $locale),
                    // Disable link previews so a code is never rendered inside
                    // a clickable preview title.
                    'disable_web_page_preview' => true,
                ]);
        } catch (ConnectionException $e) {
            Log::error('OTP Telegram delivery failed', ['error' => $e->getMessage()]);

            throw new OtpDeliveryException('The Telegram API could not be reached.', previous: $e);
        }

        $body = $response->json() ?? [];

        if ($response->failed() || ($body['ok'] ?? false) !== true) {
            Log::error('OTP Telegram rejected', [
                'status' => $response->status(),
                'description' => $body['description'] ?? $response->body(),
            ]);

            throw new OtpDeliveryException('Telegram rejected the message.');
        }

        return (string) ($body['result']['message_id'] ?? $response->status());
    }

    /**
     * Ask Telegram whether a chat id is reachable. Used before linking a phone
     * to a chat so a typo is caught at link time rather than at OTP time.
     */
    public function chatExists(string $chatId): bool
    {
        $token = config('otp.telegram.bot_token');

        if (blank($token)) {
            return false;
        }

        $base = rtrim((string) config('otp.telegram.api_base', 'https://api.telegram.org'), '/');

        try {
            $response = Http::timeout(10)->get("{$base}/bot{$token}/getChat", [
                'chat_id' => $chatId,
            ]);
        } catch (ConnectionException) {
            return false;
        }

        return $response->successful() && ($response->json('ok') ?? false) === true;
    }

    /**
     * Register the webhook Telegram calls when a user opens the bot, so a chat
     * id can be resolved to a user without the user typing it.
     */
    public function setWebhook(string $url, string $secretToken): bool
    {
        $token = config('otp.telegram.bot_token');

        if (blank($token) || blank($url)) {
            return false;
        }

        $base = rtrim((string) config('otp.telegram.api_base', 'https://api.telegram.org'), '/');

        try {
            $response = Http::timeout(10)->post("{$base}/bot{$token}/setWebhook", [
                'url' => $url,
                'secret_token' => $secretToken,
                'allowed_updates' => ['message'],
            ]);
        } catch (ConnectionException) {
            return false;
        }

        return $response->successful() && ($response->json('ok') ?? false) === true;
    }
}
