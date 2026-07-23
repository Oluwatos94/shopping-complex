<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Notifications\Http\Requests;

use ModulesShoppingComplex\Shared\Http\Requests\BaseFormRequest;

class NotificationPreferenceRequest extends BaseFormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email_enabled' => ['sometimes', 'boolean'],
            'push_enabled' => ['sometimes', 'boolean'],
            'in_app_enabled' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email_enabled.boolean' => 'Email enabled must be true or false.',
            'push_enabled.boolean' => 'Push enabled must be true or false.',
            'in_app_enabled.boolean' => 'In-app enabled must be true or false.',
        ];
    }
}
