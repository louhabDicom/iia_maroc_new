<?php

namespace App\Models;

use App\Enums\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Newsletter subscriber, double opt-in.
 *
 * `consent_locale` and `consent_ip` exist because consent has to be
 * demonstrable: a three-language site must be able to show which wording of the
 * opt-in the subscriber actually agreed to, and from where.
 */
class NewsletterSubscriber extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    protected $fillable = [
        'email', 'locale', 'status', 'consent_locale',
        'consent_ip', 'confirmed_at', 'unsubscribed_at',
    ];

    protected $hidden = ['consent_ip'];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    /** @param  Builder<NewsletterSubscriber>  $query */
    public function scopeConfirmed(Builder $query): void
    {
        $query->where('status', self::STATUS_CONFIRMED);
    }

    public function confirm(): void
    {
        $this->forceFill([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ])->save();
    }

    public function unsubscribe(): void
    {
        $this->forceFill([
            'status' => self::STATUS_UNSUBSCRIBED,
            'unsubscribed_at' => now(),
        ])->save();
    }

    public function isSubscribed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function consentLocale(): Locale
    {
        return Locale::parse($this->consent_locale);
    }
}
