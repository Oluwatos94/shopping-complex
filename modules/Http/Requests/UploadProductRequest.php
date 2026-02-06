<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'vendor';
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
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
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
            'name.required' => 'Product title is required.',
            'name.max' => 'Product title must not exceed 255 characters.',
            'price.required' => 'Price is required.',
            'price.numeric' => 'Price must be a number.',
            'price.min' => 'Price cannot be negative.',
            'image.required' => 'Product image is required.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a JPG, PNG, or WebP file.',
            'image.max' => 'The image must not exceed 5MB.',
        ];
    }
}
