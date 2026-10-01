<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A sponsor logo on the site.
 *
 * `tier` drives the layout, so ordering is by tier rank first and then the
 * explicit sort_order: sponsors must appear platinum-first without the editor
 * having to renumber everything when a tier is added.
 */
class Sponsor extends Model
{
    public const TIER_PLATINUM = 'platinum';

    public const TIER_GOLD = 'gold';

    public const TIER_SILVER = 'silver';

    public const TIER_BRONZE = 'bronze';

    public const TIER_PARTNER = 'partner';

    public const TIER_INSTITUTIONAL = 'institutional';

    /** Sponsors carried over from a previous edition, shown as a courtesy strip. */
    public const TIER_PREVIOUS = 'previous';

    /** @var array<string, int> */
    private const TIER_ORDER = [
        self::TIER_PLATINUM => 1,
        self::TIER_GOLD => 2,
        self::TIER_SILVER => 3,
        self::TIER_BRONZE => 4,
        self::TIER_PARTNER => 5,
        self::TIER_INSTITUTIONAL => 6,
        self::TIER_PREVIOUS => 7,
    ];

    protected $fillable = [
        'edition_id', 'tier', 'name', 'logo_path', 'logo_mono_path',
        'website_url', 'description', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @param  Builder<Sponsor>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** @param  Builder<Sponsor>  $query */
    public function scopeOfTier(Builder $query, string $tier): void
    {
        $query->where('tier', $tier);
    }

    /**
     * Platinum first, then gold, and so on.
     *
     * The tier is the primary sort because the visual hierarchy of the page is
     * the sponsorship hierarchy: ordering by `sort_order` alone put a bronze
     * sponsor in the first row whenever the editor had not numbered the list,
     * which is exactly the impression the tier exists to prevent.
     *
     * `sort_order` stays the tiebreaker inside a tier, so an editor can still
     * arrange two gold sponsors by hand.
     *
     * @param  Builder<Sponsor>  $query
     */
public function scopeInDisplayOrder(Builder $query): void
{
    $cases = [];
    $bindings = [];

    foreach (self::TIER_ORDER as $tier => $rank) {
        $cases[] = 'WHEN ? THEN ?';
        $bindings[] = $tier;
        $bindings[] = $rank;
    }

    $query
        ->orderByRaw('CASE `tier` '.implode(' ', $cases).' ELSE 99 END', $bindings)
        ->orderBy('sort_order')
        ->orderBy('name');
}

    public function tierRank(): int
    {
        return self::TIER_ORDER[$this->tier] ?? 99;
    }

    public function isTopTier(): bool
    {
        return in_array($this->tier, [self::TIER_PLATINUM, self::TIER_GOLD], true);
    }

    /**
     * Every tier in display order, for rendering a section per tier even when a
     * tier has no sponsor yet.
     *
     * @return array<string, int>
     */
    public static function tierOrder(): array
    {
        return self::TIER_ORDER;
    }

    public function tierLabel(): string
    {
        return __("sponsoring.tier.{$this->tier}", [], app()->getLocale());
    }
}
