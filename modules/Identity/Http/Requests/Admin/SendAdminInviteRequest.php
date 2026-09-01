<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Identity\Http\Requests\Admin;

use ModulesShoppingComplex\Shared\Http\Requests\BaseFormRequest;

class SendAdminInviteRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'An account with that email address already exists.',
        ];
    }
}
