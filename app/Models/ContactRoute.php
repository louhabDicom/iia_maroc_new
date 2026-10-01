<?php

namespace App\Models;

use App\Casts\TranslatedString;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Where a contact subject is delivered.
 *
 * Stored in the database rather than in config so the communications team can
 * reroute a mailbox without a deploy, and so a change is auditable.
 */
class ContactRoute extends Model
{
    protected $fillable = [
        'subject_type', 'label_fr', 'label_en', 'label_ar',
        'to_email', 'cc_email', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @param  Builder<ContactRoute>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function label(): string
    {
        return match (app()->getLocale()) {
            'en' => $this->label_en,
            'ar' => $this->label_ar,
            default => $this->label_fr,
        };
    }

    /**
     * Resolve the inbox for a subject, falling back to the generic route.
     *
     * A missing route must not silently drop an enquiry, so `other` is the
     * documented last resort rather than returning null.
     */
    public static function forSubject(string $subjectType): ?self
    {
        return static::query()
            ->active()
            ->whereIn('subject_type', [$subjectType, ContactMessage::SUBJECT_OTHER])
            ->orderByRaw('CASE WHEN subject_type = ? THEN 0 ELSE 1 END', [$subjectType])
            ->first();
    }
}
