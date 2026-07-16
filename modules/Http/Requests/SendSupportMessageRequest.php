<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendSupportMessageRequest extends FormRequest
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
        return [
            'content' => [
                'required',
                'string',
                'max:5000',
            ],
            'lat' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'lng' => [
                'nullable',
                'numeric',
                'between:-180,180',
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
            'content.required' => 'Please enter a message.',
            'content.max' => 'Message cannot exceed 5000 characters.',
        ];
    }
}
