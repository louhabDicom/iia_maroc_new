<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Enums\Locale;
use App\Services\Otp\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Registration input.
 *
 * Validation here is the first of two gates; the OTP is the second. What this
 * request is careful about:
 *
 *  - the phone is normalised *before* validation, so `0612345678` and
 *    `+212612345678` are the same value to the unique check and to the column.
 *    Validating the raw string and normalising afterwards looks fine and is not:
 *    the uniqueness rule would pass, and then the unique index on `users.phone`
 *    would reject the insert as a 500 instead of a field error.
 *  - the form sends the dialing code (`phone_code`) and the national number
 *    (`phone`) separately. They are merged into one international value in
 *    prepareForValidation(), so every rule below sees the final E.164 string.
 *    `phone_code` itself is never copied to the user.
 *  - the uniqueness rule deliberately covers soft-deleted rows as well. Excluding
 *    them would produce a cleaner-looking validation pass that the database
 *    index then refuses, because a unique index has no concept of `deleted_at`.
 *  - a 12-character minimum with mixed classes, which is why imported 2024
 *    hashes have to be reset rather than carried over.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Canonicalise before the rules run.
     */
    protected function prepareForValidation(): void
    {
        $code = trim((string) $this->input('phone_code'));
        $raw = trim((string) $this->input('phone'));
        $email = trim((string) $this->input('email'));

        // National digits with the trunk "0" dropped: "06 12 34 56 78" -> "612345678".
        $national = ltrim((string) preg_replace('/\D/', '', $raw), '0');

        // Use the raw value as is when the person typed a full international
        // number ("+32..." or "0032..."), or when there is nothing to combine.
        $full = (str_starts_with($raw, '+')
            || str_starts_with($raw, '00')
            || $code === ''
            || $national === '')
            ? $raw
            : $code.$national;

        $this->merge([
            'phone_code' => $code,
            'phone' => $this->canonicalise($full) ?? $raw,
            'email' => $email === '' ? $email : mb_strtolower($email),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],

            'email' => [
                'required',
                'string',
                'email:filter',
                'max:190',
                Rule::unique('users', 'email'),
            ],

            'phone_code' => [
                'required',
                'string',
                Rule::exists('countries', 'dial_code')->where('is_active', true),
            ],

            'phone' => [
                'required',
                'string',
                'normalised_phone',
                // No whereNull('deleted_at'): soft-deleted rows still hold the
                // value in the unique index.
                Rule::unique('users', 'phone'),
            ],

            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],

            'organisation' => ['nullable', 'string', 'max:190'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'country_id' => ['nullable', 'integer', Rule::exists('countries', 'id')],

            // The checkbox is the record of consent. Checked as `accepted` so a
            // missing field and a false field fail identically.
            'terms' => ['accepted'],

            'locale' => ['nullable', Rule::in(['fr', 'en', 'ar'])],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.normalised_phone' => __('validation.phone'),
            'terms.accepted' => __('register.terms_required'),
            'phone.unique' => __('validation.phone_taken'),
            'email.unique' => __('validation.email_taken'),
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => __('register.first_name'),
            'last_name' => __('register.last_name'),
            'email' => __('register.email'),
            'phone' => __('register.phone'),
            'phone_code' => __('register.phone_code'),
            'password' => __('register.password'),
            'organisation' => __('register.organisation'),
            'job_title' => __('register.job_title'),
            'city' => __('register.city'),
        ];
    }

    /**
     * Values ready for `User::create()`.
     *
     * `phone` is already the merged international value at this point, and
     * `phone_code` is intentionally not part of the returned array.
     *
     * @return array<string, mixed>
     */
    public function accountAttributes(): array
    {
        $first = $this->string('first_name')->trim()->value();
        $last = $this->string('last_name')->trim()->value();

        return [
            'first_name' => $first,
            'last_name' => $last,
            // `name` stays populated for the legacy-compatible read paths and for
            // the admin list, where one column is easier to scan than three.
            'name' => trim($first.' '.$last),
            'email' => $this->string('email')->value(),
            'phone' => $this->canonicalise((string) $this->input('phone')) ?? '',
            'password' => $this->string('password')->value(),
            'organisation' => $this->nullableString('organisation'),
            'job_title' => $this->nullableString('job_title'),
            'city' => $this->nullableString('city'),
            'country_id' => $this->input('country_id') !== null
                ? (int) $this->input('country_id')
                : null,
            'locale' => Locale::parse($this->input('locale') ?? app()->getLocale())->value,
        ];
    }

    /**
     * Canonical form, or null when the value cannot be parsed.
     *
     * null and `''` are deliberately kept distinct. `PhoneNumber::normalise()`
     * reports an unparseable number as an empty string, so returning that value
     * straight through would make `prepareForValidation()` replace what the user
     * typed with an empty one — and `required` would then report the field as
     * *missing* instead of the `normalised_phone` rule reporting it as malformed.
     * With null, `?? $raw` keeps the original input and the right rule fires.
     */
    private function canonicalise(string $phone): ?string
    {
        try {
            $canonical = PhoneNumber::normalise($phone);
        } catch (\Throwable) {
            return null;
        }

        return $canonical === '' ? null : $canonical;
    }

    private function nullableString(string $key): ?string
    {
        $value = $this->input($key);

        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return trim((string) $value);
    }
}