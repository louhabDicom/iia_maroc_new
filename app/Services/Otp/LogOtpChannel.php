<?php

namespace App\Services\Otp;

use App\Enums\OtpDriver;
use App\Enums\OtpPurpose;
use App\Exceptions\OtpDeliveryException;
use App\Services\Security\SecurityEventLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Writes the code to the application log.
 *
 * Development and CI only. `AbstractOtpChannel::send()` refuses this channel
 * outright when the app is in production, so a misconfigured deploy cannot leak
 * codes into a log file that support staff read.
 */
class LogOtpChannel extends AbstractOtpChannel
{
    public function name(): string
    {
        return OtpDriver::Log->value;
    }

    public function identifierType(): string
    {
        // Mirrors whichever channel the log driver is standing in for, so the
        // identifier is canonicalised the same way in local runs as in prod.
        return config('otp.driver') === OtpDriver::Telegram->value ? 'chat_id' : 'phone';
    }

    public function supports(string $identifier): bool
    {
        return filled($identifier);
    }

    protected function dispatch(
        string $identifier,
        string $code,
        OtpPurpose $purpose,
        string $locale,
    ): string {
        $line = sprintf(
            '[OTP:%s] %s -> %s (code=%s, ttl=%ds)',
            $this->name(),
            $purpose->value,
            $this->mask($identifier),
            $code,
            $purpose->ttlSeconds(),
        );

        // Log::notice, not Log::info: this is expected during local runs but
        // should be obvious if it ever appears in a production log.
        Log::notice($line);

        $this->events->log('otp.sent', [
            'level' => 'warning',
            'identifier' => $this->mask($identifier),
            'context' => [
                'driver' => $this->name(),
                'purpose' => $purpose->value,
                'reason' => 'log driver is development-only',
            ],
        ]);

        return 'log-'.Str::random(12);
    }
}
