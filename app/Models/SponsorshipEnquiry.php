<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A sponsorship enquiry, routed to a named contact rather than a shared inbox.
 */
class SponsorshipEnquiry extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_QUOTED = 'quoted';

    public const STATUS_WON = 'won';

    public const STATUS_LOST = 'lost';

    protected $fillable = [
        'edition_id', 'package_id', 'company', 'contact_name', 'contact_email',
        'contact_phone', 'website_url', 'message', 'status', 'handled_by', 'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return BelongsTo<SponsoringPackage, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(SponsoringPackage::class, 'package_id');
    }

    /** @return BelongsTo<User, $this> */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /** @param  Builder<SponsorshipEnquiry>  $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_NEW, self::STATUS_CONTACTED, self::STATUS_QUOTED]);
    }

    public function markHandled(User $handler, string $status = self::STATUS_CONTACTED): void
    {
        $this->forceFill([
            'status' => $status,
            'handled_by' => $handler->getKey(),
            'handled_at' => now(),
        ])->save();
    }
}
