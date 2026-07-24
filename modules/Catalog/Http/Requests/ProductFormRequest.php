<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Catalog\Http\Requests;

use ModulesShoppingComplex\Shared\Http\Requests\BaseFormRequest;

class ProductFormRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'vendor_id' => [$this->getRequiredRule(), 'integer', 'exists:users,id'],
            'category_id' => [$this->getRequiredRule(), 'integer', 'exists:categories,id'],
            'name' => [$this->getRequiredRule(), 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:products,slug,'.$this->route('id')],
            'description' => [$this->getRequiredRule(), 'string'],
            'price' => [$this->getRequiredRule(), 'numeric', 'min:0', 'max:99999999.99'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'vendor_id.required' => 'Vendor is required.',
            'vendor_id.exists' => 'Selected vendor does not exist.',
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Selected category does not exist.',
            'name.required' => 'Product name is required.',
            'name.max' => 'Product name cannot exceed 255 characters.',
            'description.required' => 'Product description is required.',
            'price.required' => 'Product price is required.',
            'price.numeric' => 'Price must be a valid number.',
            'price.min' => 'Price cannot be negative.',
            'price.max' => 'Price cannot exceed 99,999,999.99.',
            'stock.integer' => 'Stock must be a whole number.',
            'stock.min' => 'Stock cannot be negative.',
        ];
    }
}
