<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Identity\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use ModulesShoppingComplex\Identity\Enums\UserEnum;
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
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where('role', UserEnum::ADMIN->value),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'That account is already an administrator.',
        ];
    }
}
