<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use ModulesShoppingComplex\Billing\Enums\PaymentMethodEnum;
use ModulesShoppingComplex\Billing\Events\SubscriptionPaymentSucceeded;
use ModulesShoppingComplex\Services\WhatsAppApiService;

class SendSubscriptionPaymentWhatsApp implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly WhatsAppApiService $whatsApp,
    ) {}

    public function handle(SubscriptionPaymentSucceeded $event): void
    {
        if ($event->method !== PaymentMethodEnum::STELLAR) {
            return;
        }

        $to = $event->vendor->whatsapp_number;
        if ($to === null || $to === '') {
            return;
        }

        $this->whatsApp->sendText($to, sprintf(
            'Your Jiidaa subscription payment of %s is confirmed — your plan is %s.',
            '₦'.number_format($event->amount, 2),
            $event->isRenewal ? 'renewed for another month' : 'now active',
        ));
    }
}
