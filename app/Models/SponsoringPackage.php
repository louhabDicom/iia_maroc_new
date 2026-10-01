<?php

namespace App\Models;

use App\Casts\TranslatedList;
use App\Casts\TranslatedString;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A sponsorship package offered to partners.
 */
class SponsoringPackage extends Model
{
    protected $fillable = [
        'edition_id', 'code', 'name', 'benefits',
        'price_from', 'currency', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'name' => TranslatedString::class,
            'benefits' => TranslatedList::class,
            'price_from' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return HasMany<SponsorshipEnquiry, $this> */
    public function enquiries(): HasMany
    {
        return $this->hasMany(SponsorshipEnquiry::class, 'package_id');
    }

    /** @param  Builder<SponsoringPackage>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function formattedPrice(): string
    {
        if ($this->price_from === null) {
            return '';
        }

        // "from" is part of the offer, not a formatting concern.
        return __('sponsoring.from', [
            'amount' => Money::format($this->price_from, $this->currency ?? 'MAD'),
        ], app()->getLocale());
    }
}
