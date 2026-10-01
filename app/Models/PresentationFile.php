<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A speaker's uploaded presentation slides.
 *
 * Access is granted per user through {@see DownloadGrant}, replacing the single
 * hardcoded access code in the 2024 build (validate_access_code.php compared a
 * POST field against a string committed to the repository).
 *
 * The files stay on a private disk. They are never exposed as a public URL,
 * because a guessed path would otherwise be enough to download them.
 */
class PresentationFile extends Model
{
    /** @use HasFactory<\Database\Factories\PresentationFileFactory> */
    use HasFactory;

    protected $fillable = [
        'edition_id', 'speaker_id', 'conference_session_id', 'title',
        'file_path', 'thumbnail_path', 'mime_type', 'file_size',
        'sort_order', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return BelongsTo<Speaker, $this> */
    public function speaker(): BelongsTo
    {
        return $this->belongsTo(Speaker::class);
    }

    /** @return BelongsTo<ConferenceSession, $this> */
    public function conferenceSession(): BelongsTo
    {
        return $this->belongsTo(ConferenceSession::class);
    }

    /** @param  Builder<PresentationFile>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** @param  Builder<PresentationFile>  $query */
    public function scopeForSession(Builder $query, int|ConferenceSession $session): void
    {
        $query->where('conference_session_id', $session instanceof ConferenceSession ? $session->id : $session);
    }

    public function disk(): string
    {
        return config('filesystems.default');
    }

    /** Named fileExists() rather than exists() to avoid reading as "row exists". */
    public function fileExists(): bool
    {
        return Storage::disk($this->disk())->exists($this->file_path);
    }

    /**
     * Stream the file through a download route. Never returns a bare path.
     */
    public function streamDownload()
    {
        return Storage::disk($this->disk())->download($this->file_path);
    }
}
