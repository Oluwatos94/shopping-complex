<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Http\Requests;

use ModulesShoppingComplex\Shared\Http\Requests\BaseFormRequest;

class UpdateCategoryLeadCostRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lead_coin_cost' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
