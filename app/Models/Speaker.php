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
 * A conference speaker.
 *
 * The 2026 brief is strict: publish a profile only for confirmed speakers,
 * each with photo, name, job title, a three-sentence biography, country and
 * session, and only with the speaker's agreement to be published. `status` and
 * `is_published` enforce that, and `biography_sentences` is validated so a
 * one-line bio cannot slip through.
 *
 * @property string|null $biography
 * @property int|null $biography_sentences
 */
class Speaker extends Model
{
    /** @use HasFactory<\Database\Factories\SpeakerFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_DECLINED = 'declined';

    protected $fillable = [
        'edition_id', 'first_name', 'last_name', 'job_title', 'organisation',
        'country_iso2', 'biography', 'biography_sentences', 'photo_path',
        'email', 'linkedin_url', 'talk_title', 'status',
        'is_keynote', 'is_published', 'sort_order',
    ];

    protected $hidden = ['email'];

    protected function casts(): array
    {
        return [
            'job_title' => TranslatedString::class,
            'talk_title' => TranslatedString::class,
            'is_keynote' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
            'biography_sentences' => 'integer',
        ];
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_iso2', 'iso2');
    }

    /**
     * Sessions this speaker appears in, in presentation order.
     *
     * @return BelongsToMany<ConferenceSession, $this>
     */
    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(
            ConferenceSession::class,
            'session_speakers',
            'speaker_id',
            'conference_session_id',
        )
            ->withPivot(['role', 'sort_order'])
            ->orderBy('session_speakers.sort_order');
    }

    /** @return HasMany<PresentationFile, $this> */
    public function presentationFiles(): HasMany
    {
        return $this->hasMany(PresentationFile::class);
    }

    /** @param  Builder<Speaker>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->where('status', self::STATUS_CONFIRMED);
    }

    /** @param  Builder<Speaker>  $query */
    public function scopeKeynotes(Builder $query): void
    {
        $query->where('is_keynote', true);
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function initials(): string
    {
        $first = mb_substr($this->first_name ?? '', 0, 1);
        $last = mb_substr($this->last_name ?? '', 0, 1);

        return mb_strtoupper($first.$last);
    }

    /**
     * Enforces the brief's "biographie en 3 phrases" rule. Returns false when
     * the bio is missing or is not exactly three sentences.
     */
    public function hasValidBiography(): bool
    {
        if (blank($this->biography)) {
            return false;
        }

        return $this->countSentences() === 3;
    }

    public function countSentences(): int
    {
        if (blank($this->biography)) {
            return 0;
        }

        // Split on . ! ? followed by whitespace or end of string. Works for
        // Latin and Arabic punctuation alike.
        $parts = preg_split('/(?<=[.!?؟])\s+/u', trim($this->biography), -1, PREG_SPLIT_NO_EMPTY);

        return count(array_filter($parts ?? [], static fn ($p) => trim($p) !== ''));
    }
}
