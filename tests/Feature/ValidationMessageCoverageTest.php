<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every validation rule this application can reach must have a message in every
 * locale it serves.
 *
 * This exists because of a bug that only a French or Arabic visitor would ever
 * have seen: `lang/fr/validation.php` held three domain-specific messages and
 * nothing else, under a comment claiming "Laravel's built-in French messages
 * come from the framework". The framework ships `en` only, and the application
 * file is merged *over* it, so English looked fine and every other locale
 * rendered the literal `validation.required` to the customer.
 *
 * A missing key fails silently in the other direction too, which is why the
 * last test here asserts on rendered output rather than on the array.
 */
class ValidationMessageCoverageTest extends TestCase
{
    // The end-to-end test re-renders the register form, which lists countries.
    use RefreshDatabase;

    /**
     * Dotted keys, including the `min.*` / `password.*` sub-keys, because a
     * missing sub-key falls back to the raw dotted key just as a missing
     * top-level one does.
     *
     * @return list<string>
     */
    private static function reachableKeys(): array
    {
        $scalars = [
            'accepted', 'accepted_if', 'after', 'after_or_equal', 'alpha', 'alpha_dash',
            'alpha_num', 'array', 'before', 'before_or_equal', 'boolean', 'confirmed',
            'current_password', 'date', 'date_equals', 'date_format', 'decimal', 'declined',
            'different', 'digits', 'digits_between', 'distinct', 'email', 'ends_with',
            'enum', 'exists', 'file', 'filled', 'image', 'in', 'in_array', 'integer', 'ip',
            'json', 'list', 'lowercase', 'max_digits', 'mimes', 'mimetypes', 'min_digits',
            'multiple_of', 'not_in', 'not_regex', 'numeric', 'present', 'prohibited',
            'regex', 'required', 'required_array_keys', 'required_if', 'required_unless',
            'required_with', 'required_with_all', 'required_without', 'required_without_all',
            'same', 'starts_with', 'string', 'timezone', 'unique', 'uploaded', 'uppercase',
            'url', 'uuid',
        ];

        // The size-family rules are per-type; a test that only checks `min` would
        // pass with a `min.string` that does not exist.
        $sized = [];

        foreach (['between', 'gt', 'gte', 'lt', 'lte', 'max', 'min', 'size'] as $rule) {
            foreach (['array', 'file', 'numeric', 'string'] as $type) {
                $sized[] = $rule.'.'.$type;
            }
        }

        // Raised by Illuminate\Validation\Rules\Password, keyed by the failure
        // name it passes to addFailure().
        $password = [
            'password.letters', 'password.mixed', 'password.numbers',
            'password.symbols', 'password.uncompromised',
        ];

        // Application-defined rules.
        $domain = ['phone', 'phone_taken', 'email_taken'];

        return array_merge($scalars, $sized, $password, $domain);
    }

    public function test_every_reachable_rule_has_a_message_in_french(): void
    {
        $this->assertNoRawKeys('fr');
    }

    public function test_every_reachable_rule_has_a_message_in_english(): void
    {
        $this->assertNoRawKeys('en');
    }

    public function test_every_reachable_rule_has_a_message_in_arabic(): void
    {
        $this->assertNoRawKeys('ar');
    }

    /**
     * The end-to-end version: a failed registration must not put a translation
     * key on screen, whatever the locale.
     */
    public function test_a_failed_registration_never_shows_a_translation_key(): void
    {
        foreach (['fr', 'en', 'ar'] as $locale) {
            $prefix = $locale === 'fr' ? '' : $locale.'/';

            $response = $this->withHeaders([
                'Referer' => url($prefix.'register'),
                'Accept-Language' => $locale,
            ])->followingRedirects()->post($prefix.'register', [
                'first_name' => '',
                'last_name' => '',
                'email' => 'not-an-email',
                'phone' => 'not-a-phone',
                'password' => 'short',
                'password_confirmation' => 'different',
                'locale' => $locale,
            ]);

            $response->assertOk();
            $response->assertDontSee('validation.', escape: false);
        }
    }

    private function assertNoRawKeys(string $locale): void
    {
        app()->setLocale($locale);

        $missing = [];

        foreach (self::reachableKeys() as $key) {
            $translation = __("validation.$key");

            // A missing key resolves to itself. A message that happens to be
            // identical to its key would be a copy/paste error worth failing on.
            if ($translation === "validation.$key") {
                $missing[] = $key;
            }
        }

        $this->assertSame(
            [],
            $missing,
            "Untranslated validation keys in [$locale]: ".implode(', ', $missing)
        );
    }
}
