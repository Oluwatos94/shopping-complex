<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Requests;

use ModulesShoppingComplex\Shared\Http\Requests\BaseFormRequest;

class ImageUploadRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120', // 5MB in kilobytes
                'dimensions:min_width=100,min_height=100,max_width=5000,max_height=5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'At least one image is required.',
            'images.array' => 'Images must be provided as an array.',
            'images.min' => 'At least one image is required.',
            'images.max' => 'You cannot upload more than 10 images at once.',
            'images.*.required' => 'Each image file is required.',
            'images.*.image' => 'Each file must be a valid image.',
            'images.*.mimes' => 'Images must be in JPEG, JPG, PNG, or WebP format.',
            'images.*.max' => 'Each image must not exceed 5MB.',
            'images.*.dimensions' => 'Image dimensions must be between 100x100 and 5000x5000 pixels.',
        ];
    }
}
