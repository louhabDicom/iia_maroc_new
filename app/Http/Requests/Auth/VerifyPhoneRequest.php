<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The submitted verification code.
 *
 * The code is `digits` and nothing else: no trimming into a URL, no partial
 * match, and a fixed length so an oversized submission is rejected before it
 * reaches the hash comparison.
 */
class VerifyPhoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $length = (int) config('otp.length', 6);

        return [
            'code' => ['required', 'string', 'digits:'.$length, 'size:'.$length],
        ];
    }

    public function messages(): array
    {
        return [
            'code.digits' => __('otp.invalid'),
            'code.size' => __('otp.invalid'),
            'code.required' => __('otp.invalid'),
        ];
    }

    public function attributes(): array
    {
        return ['code' => __('verify.code_label')];
    }

    public function code(): string
    {
        return (string) $this->input('code');
    }
}
