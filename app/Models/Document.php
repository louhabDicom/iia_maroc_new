<?php

namespace App\Models;

use App\Casts\TranslatedString;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A downloadable document (programme, technical sheet, dossier).
 *
 * `version` and `is_provisional` exist because the brief requires the version
 * and last-updated date to be visible on every download, and because the
 * scientific programme is still being confirmed: a provisional document is
 * watermarked rather than silently presented as final.
 */
class Document extends Model
{
    public const TYPE_PROGRAMME = 'programme';

    public const TYPE_TECHNICAL_SHEET = 'technical_sheet';

    public const TYPE_SPONSORSHIP_DOSSIER = 'sponsorship_dossier';

    public const TYPE_REGISTRATION_FORM = 'registration_form';

    protected $fillable = [
        'edition_id', 'type', 'title', 'version', 'file_path', 'file_name',
        'file_size', 'mime_type', 'is_provisional', 'available_locales',
        'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'title' => TranslatedString::class,
            'available_locales' => 'array',
            'file_size' => 'integer',
            'is_provisional' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @param  Builder<Document>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** @param  Builder<Document>  $query */
    public function scopeOfType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    /** Public URL, served through the web route so the disk can stay private. */
    public function downloadUrl(): string
    {
        return route('documents.download', ['document' => $this->id]);
    }

    public function isAvailableIn(?string $locale = null): bool
    {
        $locale ??= app()->getLocale();

        // An empty list means "not language restricted".
        return $this->available_locales === null
            || $this->available_locales === []
            || in_array($locale, $this->available_locales, true);
    }

    public function formattedSize(): string
    {
        if (! $this->file_size) {
            return '';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = (int) min(floor(log((float) $this->file_size, 1024)), count($units) - 1);
        $size = $this->file_size / (1024 ** $power);

        return sprintf('%s %s', number_format($size, $power === 0 ? 0 : 1), $units[$power]);
    }
}
