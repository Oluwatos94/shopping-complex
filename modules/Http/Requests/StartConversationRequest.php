<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Identity\Models\User;

class StartConversationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        // Rules depend on user role
        if ($user->role === 'customer') {
            return [
                'vendor_id' => [
                    'required',
                    'integer',
                    Rule::exists(User::getTableName(), 'id')->where('role', 'vendor'),
                ],
                'product_id' => [
                    'nullable',
                    'integer',
                    Rule::exists(Product::getTableName(), 'id'),
                ],
            ];
        }

        // Vendor starting conversation with customer
        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists(User::getTableName(), 'id')->where('role', 'customer'),
            ],
            'product_id' => [
                'nullable',
                'integer',
                Rule::exists(Product::getTableName(), 'id'),
            ],
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
            'vendor_id.required' => 'Please select a vendor to start a conversation with.',
            'vendor_id.exists' => 'The selected vendor does not exist.',
            'customer_id.required' => 'Please select a customer to start a conversation with.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'product_id.exists' => 'The selected product does not exist.',
        ];
    }
}
