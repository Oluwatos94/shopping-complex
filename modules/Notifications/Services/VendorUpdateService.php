<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Notifications\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\Data\VendorUpdate;
use ModulesShoppingComplex\Notifications\Enums\NotificationChannelEnum;
use ModulesShoppingComplex\Notifications\Events\VendorUpdateEvent;
use ModulesShoppingComplex\Notifications\Repositories\NotificationPreferenceRepository;
use ModulesShoppingComplex\Notifications\Repositories\NotificationRepository;
use ModulesShoppingComplex\Notifications\VendorUpdateMail;
use ModulesShoppingComplex\Shared\Support\MarkdownRenderer;
use ModulesShoppingComplex\WhatsApp\Contracts\WhatsAppSender;
use ModulesShoppingComplex\WhatsApp\Support\WhatsAppPhone;

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
        if ($update->sendsTo(NotificationChannelEnum::WHATSAPP)) {
            $this->sendWhatsApp($vendor, $update);
        }

        if ($update->sendsTo(NotificationChannelEnum::EMAIL)) {
            $this->sendEmail($vendor, $update);
        }

        if ($update->sendsTo(NotificationChannelEnum::IN_APP)) {
            $this->sendInApp($vendor, $update);
        }
    }

    private function sendWhatsApp(User $vendor, VendorUpdate $update): void
    {
        $raw = (string) ($vendor->whatsapp_number ?? '');
        if ($raw === '') {
            return;
        }

        $to = WhatsAppPhone::toE164($raw);
        if ($to === null) {
            Log::warning('Vendor update WhatsApp skipped: unnormalizable number', [
                'vendor_id' => $vendor->id,
            ]);

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

        $message = MarkdownRenderer::toPlainText($update->body);

        try {
            $this->notifications->create([
                'user_id' => $vendor->id,
                'type' => self::TYPE,
                'message' => $message,
                'data' => $update->data,
            ]);

            event(new VendorUpdateEvent($vendor, $message, $update->data));
        } catch (\Throwable $e) {
            Log::warning('Vendor update in-app delivery failed', [
                'vendor_id' => $vendor->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
