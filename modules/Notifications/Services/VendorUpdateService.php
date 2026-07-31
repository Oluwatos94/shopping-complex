<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Notifications\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\Data\VendorUpdate;
use ModulesShoppingComplex\Notifications\Events\VendorUpdateEvent;
use ModulesShoppingComplex\Notifications\Repositories\NotificationPreferenceRepository;
use ModulesShoppingComplex\Notifications\Repositories\NotificationRepository;
use ModulesShoppingComplex\Notifications\VendorUpdateMail;
use ModulesShoppingComplex\WhatsApp\Contracts\WhatsAppSender;

final readonly class VendorUpdateService
{
    private const TYPE = 'vendor_update';

    public function __construct(
        private WhatsAppSender $whatsApp,
        private NotificationPreferenceRepository $preferences,
        private NotificationRepository $notifications,
    ) {}

    public function send(User $vendor, VendorUpdate $update): void
    {
        $this->sendWhatsApp($vendor, $update);
        $this->sendEmail($vendor, $update);
        $this->sendInApp($vendor, $update);
    }

    private function sendWhatsApp(User $vendor, VendorUpdate $update): void
    {
        $to = $vendor->whatsapp_number;
        if ($to === null || $to === '') {
            return;
        }

        try {
            $this->whatsApp->sendTemplate(
                $to,
                $update->templateName ?? (string) config('services.whatsapp.templates.vendor_update'),
                $update->templateLanguage ?? (string) config('services.whatsapp.template_language', 'en'),
                $update->templateComponents,
            );
        } catch (\Throwable $e) {
            Log::warning('Vendor update WhatsApp delivery failed', [
                'vendor_id' => $vendor->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendEmail(User $vendor, VendorUpdate $update): void
    {
        if (! $this->preferences->isEmailEnabled($vendor->id, self::TYPE)) {
            return;
        }

        try {
            Mail::to($vendor)->send(new VendorUpdateMail($vendor, $update));
        } catch (\Throwable $e) {
            Log::warning('Vendor update email delivery failed', [
                'vendor_id' => $vendor->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendInApp(User $vendor, VendorUpdate $update): void
    {
        if (! $this->preferences->isInAppEnabled($vendor->id, self::TYPE)) {
            return;
        }

        try {
            $this->notifications->create([
                'user_id' => $vendor->id,
                'type' => self::TYPE,
                'message' => $update->body,
                'data' => $update->data,
            ]);

            event(new VendorUpdateEvent($vendor, $update->body, $update->data));
        } catch (\Throwable $e) {
            Log::warning('Vendor update in-app delivery failed', [
                'vendor_id' => $vendor->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
