<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Services\Otp\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Profile editing input.
 *
 * The mirror image of RegisterRequest, and the same three decisions apply:
 *
 *  - the phone is normalised *before* validation, for the reason given there: the
 *    unique index on `users.phone` would reject a raw-format number as a 500
 *    rather than as a field error;
 *  - the uniqueness rules `ignore()` the account being edited rather than
 *    excluding soft-deleted rows, because a unique index has no concept of
 *    `deleted_at` and a validation that passed would still be refused by the
 *    database;
 *  - `email` is validated but *not* unique-checked against a fresh row: changing
 *    it re-opens verification, which is a controller decision and not a
 *    validation one.
 */
class UpdateProfileRequest extends FormRequest
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
        $phone = trim((string) $this->input('phone'));
        $email = trim((string) $this->input('email'));

        $this->merge([
            'phone' => $this->canonicalise($phone) ?? $phone,
            'email' => $email === '' ? $email : mb_strtolower($email),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],

            'email' => [
                'required',
                'string',
                'email:filter',
                'max:190',
                Rule::unique('users', 'email')->ignore($user?->getKey()),
            ],

            'phone' => [
                'required',
                'string',
                'max:32',
                'normalised_phone',
                Rule::unique('users', 'phone')->ignore($user?->getKey()),
            ],

            'organisation' => ['nullable', 'string', 'max:190'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'country_id' => ['nullable', 'integer', Rule::exists('countries', 'id')],
            'locale' => ['nullable', Rule::in(['fr', 'en', 'ar'])],
        ];
    }

    public function messages(): array
    {
        return [
            'normalised_phone' => __('validation.phone'),
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
            'organisation' => __('register.organisation'),
            'job_title' => __('register.job_title'),
            'city' => __('register.city'),
        ];
    }

    /**
     * The attributes to write, without touching the password or the verification
     * timestamps — both are the controller's business.
     *
     * `name` is kept in step with the two name columns for the same reason
     * RegisterRequest keeps it: the legacy read paths and the admin list read one
     * column rather than three, and a profile edit that updated `first_name` and
     * `last_name` alone would leave them showing the old name forever.
     *
     * @return array<string, mixed>
     */
    public function profileAttributes(): array
    {
        $first = $this->string('first_name')->trim()->value();
        $last = $this->string('last_name')->trim()->value();

        return [
            'first_name' => $first,
            'last_name' => $last,
            'name' => trim($first.' '.$last),
            'email' => $this->string('email')->value(),
            'phone' => $this->canonicalise((string) $this->input('phone')) ?? '',
            'organisation' => $this->nullableString('organisation'),
            'job_title' => $this->nullableString('job_title'),
            'city' => $this->nullableString('city'),
            'country_id' => $this->input('country_id') !== null
                ? (int) $this->input('country_id')
                : null,
            'locale' => $this->input('locale'),
        ];
    }

    /**
     * Whether the email the delegate typed is a different address from the one
     * on the account.
     *
     * Compared case-insensitively on the normalised value, so re-saving the form
     * without touching the field does not throw the account back into
     * unverified state.
     */
    public function emailChanged(): bool
    {
        return mb_strtolower((string) $this->user()?->email)
            !== mb_strtolower($this->string('email')->value());
    }

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
