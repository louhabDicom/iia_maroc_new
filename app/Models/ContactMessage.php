<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A contact-form submission.
 *
 * `subject_type` decides the recipient via ContactRoute rather than sending
 * everything to one mailbox: the brief requires registration, sponsoring,
 * speaker, press and general enquiries to reach different people.
 */
class ContactMessage extends Model
{
    public const SUBJECT_REGISTRATION = 'registration';

    public const SUBJECT_SPONSORING = 'sponsoring';

    public const SUBJECT_SPEAKER = 'speaker';

    public const SUBJECT_PRESS = 'press';

    public const SUBJECT_OTHER = 'other';

    public const STATUS_NEW = 'new';

    public const STATUS_OPEN = 'open';

    public const STATUS_ANSWERED = 'answered';

    public const STATUS_SPAM = 'spam';

    protected $fillable = [
        'edition_id', 'subject_type', 'name', 'email', 'phone', 'organisation',
        'message', 'locale', 'ip_address', 'status', 'internal_notes', 'handled_by', 'handled_at',
    ];

    protected $hidden = ['ip_address'];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return BelongsTo<User, $this> */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /** @return BelongsTo<ContactRoute, $this> */
    public function route(): BelongsTo
    {
        return $this->belongsTo(ContactRoute::class, 'subject_type', 'subject_type');
    }

    /** @param  Builder<ContactMessage>  $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_NEW, self::STATUS_OPEN]);
    }

    public function answer(User $handler, ?string $notes = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_ANSWERED,
            'handled_by' => $handler->getKey(),
            'handled_at' => now(),
            'internal_notes' => $notes,
        ])->save();
    }

    /**
     * Badge colour for a status.
     *
     * Spam is red rather than muted: it is the one state an operator wants to
     * recognise and remove from the queue at a glance, and giving it the same
     * treatment as "in progress" would hide it among the things to work.
     */
    public function colour(): string
    {
        return match ($this->status) {
            self::STATUS_ANSWERED => 'success',
            self::STATUS_SPAM => 'danger',
            default => 'warning',
        };
    }

    /** @return array<int, string> */
    public static function subjectTypes(): array
    {
        return [
            self::SUBJECT_REGISTRATION,
            self::SUBJECT_SPONSORING,
            self::SUBJECT_SPEAKER,
            self::SUBJECT_PRESS,
            self::SUBJECT_OTHER,
        ];
    }
}
