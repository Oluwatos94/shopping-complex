<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @method \Illuminate\Routing\Route|object|string|null route(string|null $param = null, mixed $default = null)
 * @method bool has(string|array $key)
 * @method bool hasFile(string $key) Check if the request contains a file
 * @method void merge(array $input)
 * @method \Illuminate\Http\UploadedFile|null file(string $key, mixed $default = null)
 * @method mixed input(string $key, mixed $default = null)
 * @method string method()
 */
abstract class BaseFormRequest extends FormRequest
{
    public const MAX_FILE_SIZE = 24576; // 24MB in KB

    public function authorize(): bool
    {
        return true;
    }

    protected function isUpdate(): bool
    {
        return in_array($this->method(), ['PUT', 'PATCH']);
    }

    protected function getRequiredRule(): string
    {
        return $this->isUpdate() ? 'sometimes' : 'required';
    }
}
