<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * A password change on an existing account.
 *
 * The current password is required, and that is the whole point of a separate
 * form: without it, a walk-up attacker or a borrowed unlocked laptop could lock
 * the owner out of an account that already holds paid orders, and the recovery
 * route is an email round trip. It is checked with `Hash::check()` against the
 * stored hash in the controller, not by a `current_password` rule, because the
 * rule reports failure as a validation message that does not say *why*.
 *
 * The strength rules are the same twelve characters with mixed classes that
 * registration enforces, so the two doors have one lock between them.
 */
class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'confirmed',
                'different:current_password',
                Password::min(12)->letters()->mixedCase()->numbers()->symbols(),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => __('account.current_password'),
            'password' => __('register.password'),
        ];
    }

    public function messages(): array
    {
        return [
            'password.different' => __('account.password_same'),
        ];
    }
}
