<?php

namespace App\Models;

use App\Casts\TranslatedList;
use App\Casts\TranslatedString;
use App\Casts\TranslationPayload;
use App\Enums\EditionStatus;
use App\Enums\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One edition of the conference.
 *
 * The 2024 build hardcoded 2024 into page titles, meta tags, PDFs and email
 * bodies, which is why the brief's "replace all mentions" row is a real
 * engineering task. Here, the year, venue, theme and dates live in this table
 * and every page reads from it, so the 2027 edition is a data insert.
 *
 * @property int $id
 * @property int $year
 * @property string $code
 * @property string $organiser
 * @property string $host_institute
 * @property EditionStatus $status
 * @property bool $is_current
 * @property bool $registration_open
 */
class Edition extends Model
{
    /** @use HasFactory<\Database\Factories\EditionFactory> */
    use HasFactory;

    /**
     * @var self|null memoised per request by {@see self::current()}
     */
    protected static ?self $current = null;

    protected $fillable = [
        'year', 'code', 'organiser', 'host_institute',
        'title', 'theme', 'introduction',
        'city', 'country_iso2', 'venue_name', 'venue_address',
        'venue_lat', 'venue_lng', 'venue_map_url',
        'starts_on', 'ends_on', 'languages', 'target_audience',
        'status', 'is_current', 'registration_open',
        'contact_email', 'contact_phone',
        'organiser_contact_name', 'organiser_contact_email',
        'host_contact_name', 'host_contact_email',
        'hero_image_path', 'logo_path', 'archive_note',
    ];

    protected function casts(): array
    {
        return [
            'title' => TranslatedString::class,
            'theme' => TranslatedString::class,
            'introduction' => TranslatedString::class,
            'target_audience' => TranslatedList::class,
            'languages' => 'array',
            'status' => EditionStatus::class,
            'is_current' => 'boolean',
            'registration_open' => 'boolean',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'venue_lat' => 'float',
            'venue_lng' => 'float',
        ];
    }

    // --- Relations -------------------------------------------------------

    /** @return HasMany<Organisation, $this> */
    public function organisations(): HasMany
    {
        return $this->hasMany(Organisation::class);
    }

    /** @return HasMany<Track, $this> */
    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class)->orderBy('sort_order');
    }

    /** @return HasMany<Room, $this> */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /** @return HasMany<Speaker, $this> */
    public function speakers(): HasMany
    {
        return $this->hasMany(Speaker::class);
    }

    /** @return HasMany<ConferenceSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(ConferenceSession::class);
    }

    /** @return HasMany<Sponsor, $this> */
    public function sponsors(): HasMany
    {
        return $this->hasMany(Sponsor::class);
    }

    /** @return HasMany<TicketType, $this> */
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasMany<TeamMember, $this> */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
    // --- Scopes ----------------------------------------------------------
    /** @param  Builder<Edition>  $query */
    public function scopeCurrent(Builder $query): void
    {
        $query->where('is_current', true)->where('status', EditionStatus::Published);
    }

    /** @param  Builder<Edition>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', EditionStatus::Published);
    }

    /** @param  Builder<Edition>  $query */
    public function scopeArchived(Builder $query): void
    {
        $query->where('status', EditionStatus::Archived);
    }

    // --- Accessors -------------------------------------------------------

    /** Resolved title for a specific locale, independent of the current one. */
    public function titleIn(Locale|string $locale): string
    {
        $locale = $locale instanceof Locale ? $locale : Locale::parse($locale);

        // getRawOriginal() returns the JSON string, not a decoded array, so
        // indexing it directly with a locale string raises a TypeError. The
        // cast is bypassed on purpose here: a custom cast resolves for the
        // *active* locale only, and this method must be able to ask for another.
        return TranslationPayload::resolve(
            TranslationPayload::toArray($this->getRawOriginal('title')),
            $locale->value
        ) ?? "{$this->organiser} {$this->year}";
    }

    /**
     * "16 & 17 December 2026 - Rabat" style date line, in the requested locale.
     *
     * Deliberately built from the date columns rather than a hardcoded string,
     * because the 2024 site carried the literal string "juin 2024 - Casablanca"
     * in the language files.
     */
    public function dateLine(Locale|string $locale = 'fr'): string
    {
        $locale = $locale instanceof Locale ? $locale : Locale::parse($locale);

        $format = 'j F Y';

        $start = $this->localisedDate($this->starts_on, $locale, $format);
        $end = $this->localisedDate($this->ends_on, $locale, $format);

        if ($this->starts_on->isSameDay($this->ends_on)) {
            return $start;
        }

        // Same month: "16 & 17 décembre 2026" reads better than repeating the
        // month, and matches how the brief writes the dates.
        if ($this->starts_on->month === $this->ends_on->month) {
            return $this->starts_on->format('j').' & '.$end;
        }

        return "{$start} & {$end}";
    }

    public function venueLine(Locale|string $locale = 'fr'): string
    {
        $locale = $locale instanceof Locale ? $locale : Locale::parse($locale);

        return "{$this->venue_name}, {$this->city}";
    }

    /**
     * Month names come from the app locale, so Arabic renders a real Arabic
     * month rather than the English one with an Arabic page around it.
     */
    private function localisedDate(\Illuminate\Support\Carbon $date, Locale $locale, string $format): string
    {
        $previous = app()->getLocale();
        app()->setLocale($locale->value);

        try {
            return $date->locale($locale->value)->translatedFormat($format);
        } finally {
            app()->setLocale($previous);
        }
    }

    /** The public identity string required everywhere: "Conférence ARABCIA 2026". */
    public function identityLabel(): string
    {
        return __('edition.identity', [
            'organiser' => $this->organiser,
            'year' => $this->year,
        ]);
    }

    // --- Lookups ---------------------------------------------------------

    /**
     * The edition the site is currently presenting, or null.
     *
     * Memoised for the request: this is called by nearly every view, the
     * navigation and the pricing logic alike, and a single extra query per call
     * is exactly the N+1 the model guards are there to catch.
     */
    public static function current(): ?self
    {
        return static::$current ??= static::query()->current()->first();
    }

    public static function forYear(int|string $year): ?self
    {
        return static::query()->where('year', (int) $year)->first();
    }

    /** Every edition except the current one, newest first: powers the archive. */
    public static function archive(): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()
            ->whereKeyNot(static::current()?->getKey() ?? 0)
            ->orderByDesc('year')
            ->get();
    }

    /** Drop the memoised instance; call after switching edition in tests/admin. */
    public static function forgetCurrent(): void
    {
        static::$current = null;
    }
}
