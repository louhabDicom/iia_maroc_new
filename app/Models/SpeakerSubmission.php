<?php

namespace App\Models;

use App\Casts\TranslatedString;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A call-for-speakers submission.
 *
 * The table exists from day one but the public route stays disabled until the
 * CFP opens, because the 2026 brief defers the presentation-upload form to the
 * end of the conference. `isOpen()` is the single gate for that.
 */
class SpeakerSubmission extends Model
{
    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_WAITLISTED = 'waitlisted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $fillable = [
        'edition_id', 'user_id', 'full_name', 'email', 'phone',
        'organisation', 'job_title', 'country_iso2', 'requested_format',
        'requested_track_id', 'session_title', 'abstract', 'outline',
        'status', 'review_notes', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Track, $this> */
    public function requestedTrack(): BelongsTo
    {
        return $this->belongsTo(Track::class, 'requested_track_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @param  Builder<SpeakerSubmission>  $query */
    public function scopePending(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW]);
    }

    public static function isOpen(?Edition $edition = null): bool
    {
        $edition ??= Edition::current();

        // Explicitly opt-in, so a forgotten flag closes the form rather than
        // leaving submissions open by accident.
        return (bool) config('conference.speaker_submissions_open', false)
            && $edition?->registration_open === true;
    }

    public function review(string $status, ?User $reviewer = null, ?string $notes = null): void
    {
        $this->forceFill([
            'status' => $status,
            'reviewed_by' => $reviewer?->getKey(),
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ])->save();
    }
}
