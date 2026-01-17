<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
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
                'required_without:attachment',
                'string',
                'max:5000',
            ],
            'attachment' => [
                'nullable',
                'file',
                'max:10240', // 10MB max
                // Validate both extension and MIME type for security
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain',
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
            'content.required_without' => 'Please enter a message or attach a file.',
            'content.max' => 'Message cannot exceed 5000 characters.',
            'attachment.max' => 'File size cannot exceed 10MB.',
            'attachment.mimes' => 'File type not supported. Allowed: jpg, png, gif, webp, pdf, doc, docx, xls, xlsx, txt, zip.',
        ];
    }
}
