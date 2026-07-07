<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use ModulesShoppingComplex\Events\SubscriptionRenewalFailed;
use ModulesShoppingComplex\Services\WhatsAppApiService;

class SendRenewalFailedWhatsApp implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly WhatsAppApiService $whatsApp,
    ) {}

    public function handle(SubscriptionRenewalFailed $event): void
    {
        $to = $event->vendor->whatsapp_number;
        if ($to === null || $to === '') {
            return;
        }

        $this->whatsApp->sendText(
            $to,
            'Your Jiidaa subscription renewal failed — top up your balance to keep your store active.',
        );
    }
}
