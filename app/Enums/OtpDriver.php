<?php

namespace App\Enums;

enum OtpDriver: string
{
    /** Writes the code to the log. Local development and CI only. */
    case Log = 'log';

    /** Any metered SMS gateway. Needs OTP_SMS_* credentials. */
    case Sms = 'sms';

    /**
     * Telegram Bot API. Free and not meaningfully rate-limited, which makes
     * it the only "free and unlimited" OTP channel in practice. The user must
     * have started the bot and stored their chat_id.
     */
    case Telegram = 'telegram';

    public function isProductionSafe(): bool
    {
        return $this !== self::Log;
    }

    public function label(): string
    {
        return match ($this) {
            self::Log => 'Log (development only)',
            self::Sms => 'SMS',
            self::Telegram => 'Telegram',
        };
    }
}
