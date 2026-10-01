<?php

namespace App\Enums;

/**
 * Locale + writing direction.
 *
 * The 2026 brief requires full FR/EN/AR with verified Arabic reading order, so
 * direction is a first-class property of the locale rather than something
 * derived from a string comparison in a Blade template.
 */
enum Locale: string
{
    case French = 'fr';
    case English = 'en';
    case Arabic = 'ar';

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
     * falls back to French, which is the organiser's working language.
     * `null` therefore cannot reach a translation lookup and blow up a page.
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

    public static function default(): self
    {
        $configured = (string) config('app.default_locale', 'fr');

        return self::tryFrom($configured) ?? self::French;
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
