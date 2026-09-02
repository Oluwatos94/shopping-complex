<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Identity\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use ModulesShoppingComplex\Identity\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Notifications\Enums\NotificationChannelEnum;
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
            'body' => ['required', 'string', 'max:5000'],

            'banner' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->whatsAppTemplateNeedsBanner()),
                'image',
                // Meta only accepts JPEG/PNG in a template image header.
                'mimes:'.($this->whatsAppTemplateNeedsBanner() ? 'jpeg,jpg,png' : 'jpeg,jpg,png,gif,webp'),
                'max:2048',
            ],

            // Omitted means every channel, which is what senders did before this was selectable.
            'channels' => ['sometimes', 'array', 'min:1'],
            'channels.*' => ['in:'.implode(',', NotificationChannelEnum::values())],

            'cta_label' => ['nullable', 'required_with:cta_url', 'string', 'max:40'],
            'cta_url' => ['nullable', 'required_with:cta_label', 'url', 'max:2048'],
        ];
    }

    /**
     * An approved template that declares an IMAGE header must be sent a media
     * parameter on every message, so a WhatsApp send needs a banner.
     */
    private function whatsAppTemplateNeedsBanner(): bool
    {
        if (! config('services.whatsapp.templates.vendor_update_has_image_header')) {
            return false;
        }

        $channels = $this->input('channels');

        // Absent means every channel, WhatsApp included.
        return ! is_array($channels)
            || in_array(NotificationChannelEnum::WHATSAPP->value, $channels, true);
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
            'banner.required' => 'The WhatsApp template needs a banner image — attach one, or untick WhatsApp.',
            'banner.image' => 'The banner must be an image.',
            'banner.mimes' => 'WhatsApp only accepts a JPG or PNG banner.',
            'banner.max' => 'The banner must be 2 MB or smaller.',
            'channels.min' => 'Pick at least one channel to send on.',
            'channels.*.in' => 'That is not a channel we can send on.',
        ];
    }
}
