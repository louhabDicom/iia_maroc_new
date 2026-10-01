<?php

namespace App\Models;

use App\Casts\TranslatedString;
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
    /** @use HasFactory<\Database\Factories\OrganisationFactory> */
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
}
