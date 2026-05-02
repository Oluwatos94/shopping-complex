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
        $isUpdate = $this->route('productId') !== null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'pay_on_delivery' => ['boolean'],
            'is_returnable' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
            'video' => ['nullable', 'file', 'mimes:mp4,mov,avi,webm,mkv', 'max:102400'],
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
            'description.required' => 'Product description is required.',
            'description.max' => 'Description must not exceed 2000 characters.',
            'price.required' => 'Price is required.',
            'price.numeric' => 'Price must be a number.',
            'price.min' => 'Price cannot be negative.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a JPG, PNG, or WebP file.',
            'image.max' => 'The image must not exceed 10MB.',
            'video.mimes' => 'The video must be MP4, MOV, AVI, WebM, or MKV.',
            'video.max' => 'The video must not exceed 100MB.',
        ];
    }
}
