<?php

namespace App\Enums;

/**
 * Locale + writing direction.
 *
 * The 2026 brief requires full AR/FR/EN with verified Arabic reading order, so
 * direction is a first-class property of the locale rather than something
 * derived from a string comparison in a Blade template.
 *
 * The case order is meaningful and is not alphabetical: `Locale::cases()` drives
 * `options()`, which drives the language switcher, and the switcher lists the
 * site's default language first. Arabic leads because Arabic is the default.
 */
enum Locale: string
{
    case Arabic = 'ar';
    case French = 'fr';
    case English = 'en';

    public function label(): string
    {
        return match ($this) {
            self::French => 'Français',
            self::English => 'English',
            self::Arabic => 'العربية',
        };
    }

    public function nativeLabel(): string
    {
        return match ($this) {
            self::French => 'Français',
            self::English => 'English',
            self::Arabic => 'العربية',
        };
    }

    public function direction(): string
    {
        return $this === self::Arabic ? 'rtl' : 'ltr';
    }

    public function isRtl(): bool
    {
        return $this === self::Arabic;
    }

    /**
     * Laravel convention for this locale: Arabic should not be uppercased
     * (no case distinction), and French typography needs non-breaking spaces
     * before certain punctuation.
     */
    public function translationPath(): string
    {
        return 'lang/'.$this->value;
    }

    /**
     * Note: currency is deliberately NOT a property of the locale.
     *
     * The 2026 brief sells in MAD and USD regardless of the visitor's language,
     * so a `currency()` method on the locale would be a trap: it would suggest
     * that a French visitor pays in euros. Pricing currency comes from the
     * ticket type; the locale only decides formatting.
     */
    public function priceFormat(): string
    {
        return $this === self::French ? 'fr_MA' : 'en_US';
    }

    /**
     * Parse a stored or submitted locale tag.
     *
     * Accepts `ar`, `ar-EG`, `ar_EG` and `AR`, and never throws: an unknown tag
     * falls back to Arabic, which is the site's default language. `null`
     * therefore cannot reach a translation lookup and blow up a page.
     */
    public static function parse(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return self::default();
        }

        return self::tryFrom(strtolower(substr(trim($value), 0, 2))) ?? self::default();
    }

    /**
     * The locale served at an unprefixed address.
     *
     * Read from config rather than hardcoded here so a deployment can change it
     * without a code change; the `?? self::Arabic` is only reached if the config
     * key is missing or holds something that is not a locale at all, and it must
     * agree with `config/app.php` or the switcher would build wrong URLs.
     */
    public static function default(): self
    {
        $configured = (string) config('app.default_locale', 'ar');

        return self::tryFrom($configured) ?? self::Arabic;
    }

    /**
     * The URL path segment for this locale, or null when it is the default.
     *
     * The one place that decides "does this language get a prefix?". Both the
     * switcher's redirect and the layout's hreflang links have to agree with the
     * router, and they agree because they all ask this.
     */
    public function prefix(): ?string
    {
        return $this === self::default() ? null : $this->value;
    }

    public static function fromRequest(mixed $value): self
    {
        return self::parse($value);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
