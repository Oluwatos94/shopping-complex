<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Identity\Http\Requests\Admin;

use ModulesShoppingComplex\Identity\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Shared\Http\Requests\BaseFormRequest;

class SendVendorReminderRequest extends BaseFormRequest
{
    /**
     * Filter selectors accepted when target is "status": the onboarding
     * statuses plus the subscription-expiring cohort.
     *
     * @return array<int, string>
     */
    public static function statusFilters(): array
    {
        return [...VendorOnboardingStatusEnum::values(), 'subscription_expiring'];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'target' => ['required', 'in:all,status,selection'],

            'status' => ['required_if:target,status', 'in:'.implode(',', self::statusFilters())],

            'vendor_ids' => ['required_if:target,selection', 'array', 'min:1'],
            'vendor_ids.*' => ['integer', 'exists:users,id'],

            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000'],

            'cta_label' => ['nullable', 'required_with:cta_url', 'string', 'max:40'],
            'cta_url' => ['nullable', 'required_with:cta_label', 'url', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target.in' => 'Choose a valid recipient group.',
            'status.required_if' => 'Select which vendor group to remind.',
            'vendor_ids.required_if' => 'Select at least one vendor.',
            'cta_label.required_with' => 'A button label is required when a link is provided.',
            'cta_url.required_with' => 'A link is required when a button label is provided.',
        ];
    }
}
