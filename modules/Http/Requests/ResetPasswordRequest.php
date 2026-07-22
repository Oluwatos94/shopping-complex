<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Requests;

use Illuminate\Validation\Rules\Password;
use ModulesShoppingComplex\Shared\Http\Requests\BaseFormRequest;

class ResetPasswordRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $passwordRules = [
            'required',
            'string',
            'confirmed',
            Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols(),
        ];

        // Only check for compromised passwords in production
        if (! app()->environment('testing')) {
            $passwordRules[] = Password::min(8)->uncompromised();
        }

        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => $passwordRules,
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.min' => 'The password must be at least 8 characters.',
            'password.mixed' => 'The password must contain both uppercase and lowercase letters.',
            'password.numbers' => 'The password must contain at least one number.',
            'password.symbols' => 'The password must contain at least one symbol.',
            'password.uncompromised' => 'The given password has appeared in a data leak. Please choose a different password.',
        ];
    }
}
