<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ISO country, with a name in each supported language.
 *
 * Used for participant country selection and the "dimension internationale"
 * requirement on the target-audience page.
 */
class Country extends Model
{
    /** @use HasFactory<\Database\Factories\CountryFactory> */
    use HasFactory;

    /**
     * Reference data, so no timestamps.
     *
     * `countries` is an ISO list: rows are inserted once and essentially never
     * modified. `updated_at` on it would be meaningless, and pretending otherwise
     * makes every insert fail on a missing column rather than on a real mistake.
     */
    public $timestamps = false;

    protected $fillable = [
        'iso2', 'iso3', 'name_fr', 'name_en', 'name_ar', 'phone_code', 'dial_code', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'country_id');
    }

    public function name(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return match ($locale) {
            'ar' => $this->name_ar,
            'en' => $this->name_en,
            default => $this->name_fr,
        };
    }
}
