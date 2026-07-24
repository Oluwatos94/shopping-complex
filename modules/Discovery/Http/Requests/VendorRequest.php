<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Discovery\Http\Requests;

use ModulesShoppingComplex\Shared\Http\Requests\BaseFormRequest;

class VendorRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'integer', 'min:1', 'max:20'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', 'in:distance,rating,response_time,newest'],
            'verified_only' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.numeric' => 'Latitude must be a valid number.',
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.numeric' => 'Longitude must be a valid number.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
            'radius.integer' => 'Radius must be a whole number.',
            'radius.min' => 'Radius must be at least 1 kilometer.',
            'radius.max' => 'Radius cannot exceed 20 kilometers.',
            'search.max' => 'Search query cannot exceed 255 characters.',
            'sort_by.in' => 'Invalid sort option. Valid options are: distance, rating, response_time, newest.',
            'verified_only.boolean' => 'Verified filter must be true or false.',
            'page.integer' => 'Page must be a valid number.',
            'page.min' => 'Page number must be at least 1.',
        ];
    }

    /**
     * Get validated filters for vendor search
     *
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return [
            'latitude' => $this->input('latitude'),
            'longitude' => $this->input('longitude'),
            'radius' => $this->input('radius', 5),
            'category_id' => $this->input('category_id') ? (int) $this->input('category_id') : null,
            'search' => $this->input('search'),
            'sort_by' => $this->input('sort_by', 'distance'),
            'verified_only' => $this->boolean('verified_only', false),
            'page' => $this->input('page', 1),
        ];
    }
}
