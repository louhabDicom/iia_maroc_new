<?php

namespace App\Models;

use App\Casts\TranslatedString;
use Database\Factories\OrganisationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ARABCIA (organiser) or IIA Maroc (host institute).
 *
 * The 2026 brief explicitly asks for two distinct blocks plus verified
 * institutional data, and forbids the old "ARABCIIA" misspelling, so the code
 * is the canonical spelling and the label is always translatable.
 */
class Organisation extends Model
{
    /** @use HasFactory<OrganisationFactory> */
    use HasFactory;

    protected $fillable = [
        'edition_id', 'code', 'name', 'description', 'role',
        'logo_path', 'logo_mono_path', 'website_url', 'sort_order', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'name' => TranslatedString::class,
            'description' => TranslatedString::class,
            'role' => TranslatedString::class,
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /**
     * Only the organisations a visitor may see.
     *
     * Present on every other content model in the app. Its absence here meant
     * the home page could not filter, which is why that page had no organisers
     * band at all: the query that would have produced one could not be written.
     *
     * @param  Builder<Organisation>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }
}
