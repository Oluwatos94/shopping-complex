<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Identity\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use ModulesShoppingComplex\Billing\Enums\VendorSubscriptionStatusEnum;
use ModulesShoppingComplex\Identity\Http\Requests\Admin\SendVendorReminderRequest;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\Data\VendorUpdate;
use ModulesShoppingComplex\Notifications\Jobs\SendVendorReminder;

final readonly class VendorReminderService
{
    private const EXPIRING_WITHIN_DAYS = 7;

    /**
     * Resolve the targeted vendors and queue a reminder for each.
     *
     * @param  array<string, mixed>  $data  Validated {@see SendVendorReminderRequest} payload
     * @return int Number of vendors the reminder was queued for
     */
    public function queueReminders(array $data): int
    {
        $vendors = $this->resolveRecipients($data);

        foreach ($vendors as $vendor) {
            SendVendorReminder::dispatch($vendor, $this->updateFor($vendor, $data));
        }

        return $vendors->count();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, User>
     */
    public function resolveRecipients(array $data): Collection
    {
        $query = User::query()->where('role', 'vendor');

        match ($data['target']) {
            'selection' => $query->whereIn('id', $data['vendor_ids']),
            'status' => $this->applyStatusFilter($query, (string) $data['status']),
            default => null, // 'all' — every vendor
        };

        return $query->get();
    }

    /**
     * @param  Builder<User>  $query
     */
    private function applyStatusFilter(Builder $query, string $status): void
    {
        if ($status === 'subscription_expiring') {
            $query->whereHas('subscriptions', fn (Builder $q) => $q
                ->where('status', VendorSubscriptionStatusEnum::ACTIVE->value)
                ->whereBetween('expires_at', [now(), now()->addDays(self::EXPIRING_WITHIN_DAYS)]));

            return;
        }

        $query->whereHas('vendorOnboarding', fn (Builder $q) => $q->where('status', $status));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function updateFor(User $vendor, array $data): VendorUpdate
    {
        $subject = (string) $data['subject'];
        $bannerUrl = $data['banner_url'] ?? null;

        return new VendorUpdate(
            subject: $subject,
            body: (string) $data['body'],
            ctaLabel: $data['cta_label'] ?? null,
            ctaUrl: $data['cta_url'] ?? null,
            templateComponents: $this->templateComponents($vendor, $subject, $bannerUrl),
            data: array_filter([
                'action' => 'admin_reminder',
                'subject' => $subject,
                'banner_url' => $bannerUrl,
            ], fn ($value) => $value !== null),
            bannerUrl: $bannerUrl,
            channels: $data['channels'] ?? [],
        );
    }

    /**
     * The image header is only sent when the approved Meta template actually
     * declares one — otherwise Meta rejects the whole message.
     *
     * @return array<int, array<string, mixed>>
     */
    private function templateComponents(User $vendor, string $subject, ?string $bannerUrl): array
    {
        $components = [];

        if ($bannerUrl !== null && config('services.whatsapp.templates.vendor_update_has_image_header')) {
            $components[] = [
                'type' => 'header',
                'parameters' => [['type' => 'image', 'image' => ['link' => $bannerUrl]]],
            ];
        }

        $components[] = [
            'type' => 'body',
            'parameters' => [
                ['type' => 'text', 'text' => $vendor->name],
                ['type' => 'text', 'text' => $subject],
            ],
        ];

        return $components;
    }
}
