<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VendorRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only customers can register as vendors
        return $this->user()?->role === 'customer';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'bio' => ['required', 'string', 'max:1000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'whatsapp_number' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
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
            'business_name.required' => 'Business name is required.',
            'business_name.max' => 'Business name must not exceed 255 characters.',
            'bio.required' => 'Business description is required.',
            'bio.max' => 'Business description must not exceed 1000 characters.',
            'category_id.required' => 'Please select a business category.',
            'category_id.exists' => 'The selected category is invalid.',
            'avatar.image' => 'The avatar must be an image.',
            'avatar.mimes' => 'The avatar must be a JPG, PNG, or WebP file.',
            'avatar.max' => 'The avatar must not exceed 5MB.',
            'whatsapp_number.required' => 'WhatsApp number is required.',
            'city.required' => 'City is required.',
            'state.required' => 'State is required.',
            'latitude.required' => 'Please drop a pin on the map to set your location.',
            'longitude.required' => 'Please drop a pin on the map to set your location.',
            'latitude.between' => 'Invalid coordinates — please re-pin your location on the map.',
            'longitude.between' => 'Invalid coordinates — please re-pin your location on the map.',
        ];
    }
}
