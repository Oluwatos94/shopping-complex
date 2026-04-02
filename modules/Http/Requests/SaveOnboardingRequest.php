<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveOnboardingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only vendors can save onboarding (verification) progress
        return $this->user()?->role === 'vendor';
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('business_info') && is_string($this->input('business_info'))) {
            $this->merge([
                'business_info_decoded' => json_decode($this->input('business_info'), true) ?? [],
            ]);
        }

        if ($this->has('bank_details') && is_string($this->input('bank_details'))) {
            $this->merge([
                'bank_details_decoded' => json_decode($this->input('bank_details'), true) ?? [],
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_info' => ['required', 'string', 'json'],
            'bank_details' => ['required', 'string', 'json'],
            'current_step' => ['required', 'integer', 'min:1', 'max:4'],
            'business_info_decoded.whatsapp_number' => ['required', 'string', 'max:20'],
            'certificate_of_incorporation' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'government_issued_id' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'proof_of_address' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
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
            'business_info.required' => 'Business information is required.',
            'business_info.json' => 'Business information must be valid JSON.',
            'bank_details.required' => 'Bank details are required.',
            'bank_details.json' => 'Bank details must be valid JSON.',
            'current_step.min' => 'Invalid step number.',
            'current_step.max' => 'Invalid step number.',
            '*.mimes' => 'The :attribute must be a PDF, JPG, or PNG file.',
            '*.max' => 'The :attribute must not exceed 5MB.',
        ];
    }

    /**
     * Get the decoded business info.
     *
     * @return array<string, mixed>
     */
    public function getBusinessInfo(): array
    {
        return json_decode($this->input('business_info', '{}'), true) ?? [];
    }

    /**
     * Get the decoded bank details.
     *
     * @return array<string, mixed>
     */
    public function getBankDetails(): array
    {
        return json_decode($this->input('bank_details', '{}'), true) ?? [];
    }

    /**
     * Get the uploaded files.
     *
     * @return array<string, \Illuminate\Http\UploadedFile|null>
     */
    public function getUploadedFiles(): array
    {
        return [
            'certificate_of_incorporation' => $this->file('certificate_of_incorporation'),
            'government_issued_id' => $this->file('government_issued_id'),
            'proof_of_address' => $this->file('proof_of_address'),
        ];
    }
}
