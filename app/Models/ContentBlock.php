<?php

namespace App\Models;

use App\Casts\TranslationPayload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Editorial copy an editor can change without a deploy.
 *
 * Keys are stable identifiers, never the copy itself, so a translation can be
 * revised without touching a Blade template.
 *
 * `value` is deliberately cast as a plain array rather than as
 * TranslatedString: the column holds either a string or a list depending on
 * `value_type`, and a single-value cast would return null for every list block.
 * Resolution is therefore explicit through {@see self::text()} and
 * {@see self::items()}, which are the only two shapes a template should need.
 */
class ContentBlock extends Model
{
    public const TYPE_TEXT = 'text';

    public const TYPE_LIST = 'list';

    public const TYPE_HTML = 'html';

    protected $fillable = [
        'edition_id', 'key', 'group', 'value', 'value_type',
        'description', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_published' => 'boolean',
        ];
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @param  Builder<ContentBlock>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** @param  Builder<ContentBlock>  $query */
    public function scopeInGroup(Builder $query, string $group): void
    {
        $query->where('group', $group);
    }

    /** @param  Builder<ContentBlock>  $query */
    public function scopeKeyed(Builder $query, string ...$keys): void
    {
        $query->whereIn('key', $keys);
    }

    /**
     * The raw per-locale map, unfiltered.
     *
     * Deliberately not {@see TranslationPayload::toArray()}: that helper keeps
     * string values only, and a list block's per-locale value is an array, so
     * using it here would silently discard every list block.
     *
     * @return array<string, mixed>
     */
    private function translations(): array
    {
        $value = $this->value;

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    /**
     * The block as a string in the active locale, or null for a list block.
     */
    public function text(): ?string
    {
        if ($this->isList()) {
            return null;
        }

        return TranslationPayload::resolve(
            TranslationPayload::toArray($this->translations()),
            app()->getLocale()
        );
    }

    /**
     * The block as a list in the active locale.
     *
     * @return array<int, string>
     */
    public function items(): array
    {
        if (! $this->isList()) {
            $text = $this->text();

            return $text === null ? [] : [$text];
        }

        $resolved = $this->translations()[app()->getLocale()] ?? null;

        if ($resolved === null) {
            return [];
        }

        if (is_string($resolved)) {
            // A single string where a list was expected: one bullet, not an
            // error, because that is what an editor typing into one field
            // produces.
            return [$resolved];
        }

        if (! is_array($resolved)) {
            return [];
        }

        return array_values(array_map(
            static fn ($item) => is_array($item)
                ? (TranslationPayload::resolve(TranslationPayload::toArray($item), app()->getLocale()) ?? '')
                : (string) $item,
            $resolved
        ));
    }

    public function isList(): bool
    {
        return $this->value_type === self::TYPE_LIST;
    }

    public function isHtml(): bool
    {
        return $this->value_type === self::TYPE_HTML;
    }
}
