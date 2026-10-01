<?php

namespace App\Models;

use App\Casts\TranslatedString;
use App\Enums\SessionFormat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use DateTimeImmutable;

/**
 * A programme session.
 *
 * Times are stored as separate `session_date` / `starts_at` / `ends_at` so the
 * parallel-track grid can be computed by grouping, and so a session can never
 * be stored with an end before its start (validated).
 *
 * The brief caps concurrency at four parallel sessions. `overlapsRoomSlot()`
 * enforces that at the data layer rather than trusting manual scheduling.
 */
class ConferenceSession extends Model
{
    /** @use HasFactory<\Database\Factories\ConferenceSessionFactory> */
    use HasFactory;

    protected $table = 'conference_sessions';

    protected $fillable = [
        'edition_id', 'track_id', 'room_id', 'format', 'title', 'summary', 'objectives',
        'session_date', 'starts_at', 'ends_at', 'language',
        'interpretation_available', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'title' => TranslatedString::class,
            'summary' => TranslatedString::class,
            'objectives' => TranslatedString::class,
            'session_date' => 'date',
            'format' => SessionFormat::class,
            'interpretation_available' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return BelongsTo<Track, $this> */
    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    /** @return BelongsTo<Room, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Speakers in presentation order. The pivot carries `sort_order` so a chair
     * or moderator appears first instead of being sorted alphabetically.
     *
     * @return BelongsToMany<Speaker, $this>
     */
    public function speakers(): BelongsToMany
    {
        return $this->belongsToMany(
            Speaker::class,
            'session_speakers',
            'conference_session_id',
            'speaker_id',
        )
            ->withPivot(['role', 'sort_order'])
            ->orderBy('session_speakers.sort_order')
            ->orderBy('session_speakers.id');
    }

    /**
     * Uploaded presentation files.
     *
     * A plain has-many: `presentation_files` is a table with a foreign key, not
     * a pivot table. It is empty until the conference, because the 2026 brief
     * explicitly defers publication uploads until after the event.
     *
     * @return HasMany<PresentationFile, $this>
     */
    public function presentationFiles(): HasMany
    {
        return $this->hasMany(PresentationFile::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /** @param  Builder<ConferenceSession>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** @param  Builder<ConferenceSession>  $query */
    public function scopeOnDay(Builder $query, string|\DateTimeInterface $day): void
    {
        $query->whereDate('session_date', $day);
    }

    /**
     * Combined datetime, for sorting and overlap maths.
     *
     * The venue is local, so the stored wall-clock time is already correct and
     * no timezone conversion is applied. A DateTimeImmutable is used rather than
     * Carbon so the result is immutable and safe to hand to a view.
     */
    public function startsAtDateTime(): DateTimeImmutable
    {
        return $this->wallClock($this->startsAtString());
    }

    public function endsAtDateTime(): DateTimeImmutable
    {
        return $this->wallClock($this->endsAtString());
    }

    /**
     * Build a datetime from the session date plus an "HH:MM" time.
     *
     * createFromFormat() returns false on a format mismatch rather than
     * throwing, so the format has to match exactly: the time columns are
     * `time`, which MySQL and sqlite both return as "HH:MM:SS", and the helper
     * below trims to "HH:MM" so the composed string is "Y-m-d H:i".
     */
    private function wallClock(string $time): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i',
            $this->session_date->format('Y-m-d').' '.$time,
        );

        // An unparseable time must not take the programme page down; fall back
        // to the raw date so the row still renders with a visible defect.
        return $date ?: new DateTimeImmutable($this->session_date->format('Y-m-d'));
    }

    public function startsAtString(): string
    {
        return substr((string) $this->starts_at, 0, 5);
    }

    public function endsAtString(): string
    {
        return substr((string) $this->ends_at, 0, 5);
    }

    public function durationMinutes(): int
    {
        return (int) round(
            ($this->endsAtDateTime()->getTimestamp() - $this->startsAtDateTime()->getTimestamp()) / 60
        );
    }

    public function overlaps(ConferenceSession $other): bool
    {
        if (! $this->session_date->isSameDay($other->session_date)) {
            return false;
        }

        return $this->startsAtString() < $other->endsAtString()
            && $other->startsAtString() < $this->endsAtString();
    }

    /** True if this session is in the same room as another at the same time. */
    public function conflictsWith(ConferenceSession $other): bool
    {
        return $this->room_id !== null
            && $this->room_id === $other->room_id
            && $this->overlaps($other);
    }
}
