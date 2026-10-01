<?php

namespace App\Models;

use App\Casts\TranslatedString;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    /** @use HasFactory<\Database\Factories\RoomFactory> */
    use HasFactory;

    protected $fillable = [
        'edition_id', 'code', 'name', 'capacity', 'floor', 'sort_order', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'name' => TranslatedString::class,
            'capacity' => 'integer',
            'floor' => 'integer',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
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

    /** @param  Builder<Room>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }
}
