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
 * SMS delivery.
 *
 * Generic across providers: the gateway is selected by
 * `config('otp.sms.driver')` and each implementation is a small payload
 * builder. Only the outbound call is provider-specific; the rest of the
 * application is unaware of which gateway is in use.
 *
 * Honest limitation: no SMS provider is free and unlimited. Every one meters
 * messages, and international delivery to non-Moroccan numbers costs more. This
 * channel exists for production quality; see {@see TelegramOtpChannel} for the
 * free alternative.
 */
class SmsOtpChannel extends AbstractOtpChannel
{
    public function name(): string
    {
        return OtpDriver::Sms->value;
    }

    public function supports(string $identifier): bool
    {
        return str_starts_with($identifier, '+');
    }

    public function identifierType(): string
    {
        return 'phone';
    }

    protected function dispatch(
        string $identifier,
        string $code,
        OtpPurpose $purpose,
        string $locale,
    ): string {
        $gateway = config('otp.sms.driver', 'twilio');

        $payload = $this->buildPayload($gateway, $identifier, $code, $purpose, $locale);

        try {
            $response = match ($gateway) {
                'vonage' => $this->sendVonage($payload),
                'infobip' => $this->sendInfobip($payload),
                'generic' => $this->sendGeneric($payload),
                default => $this->sendTwilio($payload),
            };
        } catch (ConnectionException $e) {
            Log::error('OTP SMS gateway unreachable', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            throw new OtpDeliveryException('The SMS gateway could not be reached.', previous: $e);
        }

        $status = $response->status();
        $body = $response->json() ?? [];

        if ($response->failed()) {
            // The response body may contain the message text, never the code.
            Log::error('OTP SMS rejected by gateway', [
                'gateway' => $gateway,
                'status' => $status,
                'error' => $body['message'] ?? $response->body(),
            ]);

            throw new OtpDeliveryException('The SMS gateway rejected the message.');
        }

        return (string) ($body['sid'] ?? $body['messageId'] ?? $body['messages'][0]['id'] ?? $status);
    }

    /**
     * Build the provider-agnostic message.
     *
     * The purpose is part of the text: "code 123456" sent during a password
     * reset reads as a login code in a list of messages, and a code entered into
     * the wrong form is the start of a support call.
     *
     * @return array<string, mixed>
     */
    private function buildPayload(
        string $gateway,
        string $identifier,
        string $code,
        OtpPurpose $purpose,
        string $locale,
    ): array {
        $from = config('otp.sms.from');

        if (blank($from)) {
            throw OtpDeliveryException::driverMisconfigured('sms', 'OTP_SMS_FROM');
        }

        return [
            'to' => ltrim($identifier, '+'),
            'from' => $from,
            'text' => $this->message($code, $purpose, $locale),
        ];
    }

    private function sendTwilio(array $payload)
    {
        $sid = config('otp.sms.sid');
        $token = config('otp.sms.token');

        if (blank($sid) || blank($token)) {
            throw OtpDeliveryException::driverMisconfigured('sms', 'OTP_SMS_SID / OTP_SMS_TOKEN');
        }

        return Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post('https://api.twilio.com/2010-04-01/Accounts/'.$sid.'/Messages.json', [
                'To' => $payload['to'],
                'From' => $payload['from'],
                'Body' => $payload['text'],
            ]);
    }

    private function sendVonage(array $payload)
    {
        $key = config('otp.sms.api_key');

        if (blank($key)) {
            throw OtpDeliveryException::driverMisconfigured('sms', 'OTP_SMS_API_KEY');
        }

        return Http::asForm()->post('https://rest.nexmo.com/sms/json', [
            'api_key' => $key,
            'api_secret' => config('otp.sms.token'),
            'to' => $payload['to'],
            'from' => $payload['from'],
            'text' => $payload['text'],
        ]);
    }

    private function sendInfobip(array $payload)
    {
        $base = config('otp.sms.base_url');
        $key = config('otp.sms.api_key');

        if (blank($base) || blank($key)) {
            throw OtpDeliveryException::driverMisconfigured('sms', 'OTP_SMS_BASE_URL / OTP_SMS_API_KEY');
        }

        return Http::withBasicAuth($key, '')
            ->post(rtrim($base, '/').'/sms/2/text/advanced', [
                'messages' => [[
                    'from' => $payload['from'],
                    'to' => [ltrim($payload['to'], '+')],
                    'text' => $payload['text'],
                ]],
            ]);
    }

    /**
     * Any provider exposing a single JSON POST endpoint. Useful for a local
     * Kannel / Jasmin / SMPP bridge, which is common for Moroccan operators.
     */
    private function sendGeneric(array $payload)
    {
        $base = config('otp.sms.base_url');

        if (blank($base)) {
            throw OtpDeliveryException::driverMisconfigured('sms', 'OTP_SMS_BASE_URL');
        }

        return Http::withToken((string) config('otp.sms.api_key'))
            ->acceptJson()
            ->post(rtrim($base, '/').'/sms', [
                'to' => $payload['to'],
                'from' => $payload['from'],
                'text' => $payload['text'],
            ]);
    }
}
