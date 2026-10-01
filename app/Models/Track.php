<?php

namespace App\Models;

use App\Casts\TranslatedString;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A workshop axis for the 2026 programme.
 *
 * The brief names three axes: "IA et transformation", "Résilience",
 * "Auditeur de demain". Modelled as rows so a fourth axis next year is data.
 */
class Track extends Model
{
    /** @use HasFactory<\Database\Factories\TrackFactory> */
    use HasFactory;

    protected $fillable = [
        'edition_id', 'code', 'name', 'description', 'colour', 'sort_order', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'name' => TranslatedString::class,
            'description' => TranslatedString::class,
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return HasMany<ConferenceSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(ConferenceSession::class);
    }

    /** @return BelongsToMany<Speaker, $this> */
    public function speakers(): BelongsToMany
    {
        return $this->belongsToMany(Speaker::class, 'session_speakers');
    }

    /** @param  Builder<Track>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }
}
