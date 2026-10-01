<?php

namespace App\Models;

use App\Casts\TranslatedString;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A named member of the organising, scientific, logistics or press team.
 */
class TeamMember extends Model
{
    public const TEAM_ORGANISING = 'organising_committee';

    public const TEAM_SCIENTIFIC = 'scientific';

    public const TEAM_LOGISTICS = 'logistics';

    public const TEAM_PRESS = 'press';

    public const TEAM_HOST_INSTITUTE = 'host_institute';

    protected $fillable = [
        'edition_id', 'team', 'name', 'role', 'organisation', 'bio',
        'email', 'phone', 'photo_path', 'linkedin_url', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'role' => TranslatedString::class,
            'bio' => TranslatedString::class,
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @param  Builder<TeamMember>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** @param  Builder<TeamMember>  $query */
    public function scopeInTeam(Builder $query, string $team): void
    {
        $query->where('team', $team);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $letters = array_map(
            static fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)),
            array_filter($parts)
        );

        return mb_substr(implode('', $letters), 0, 2);
    }
}
