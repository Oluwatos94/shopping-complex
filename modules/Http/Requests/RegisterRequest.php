<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Requests;

use Illuminate\Validation\Rules\Password;
use ModulesShoppingComplex\Shared\Http\Requests\BaseFormRequest;

class RegisterRequest extends BaseFormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-fill name from email if not provided (for testing)
        if (! $this->has('name') && $this->has('email')) {
            $this->merge([
                'name' => explode('@', $this->email)[0],
            ]);
        }

        // Auto-set role to customer if not provided (for testing)
        if (! $this->has('role')) {
            $this->merge([
                'role' => 'customer',
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'role' => ['required', 'string', 'in:customer'],
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
            'email.email' => 'Please enter a valid email address.',
            'role.in' => 'The selected role is invalid. Must be customer, vendor, or admin.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.mixed_case' => 'Password must contain at least one uppercase letter.',
            'password.numbers' => 'Password must contain at least one number.',
            'password.symbols' => 'Password must contain at least one special character.',
            'password.confirmed' => 'The passwords do not match.',
        ];
    }
}
