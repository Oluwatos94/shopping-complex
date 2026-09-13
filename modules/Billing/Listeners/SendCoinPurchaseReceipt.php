<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use ModulesShoppingComplex\Billing\Events\CoinPackPurchased;
use ModulesShoppingComplex\WhatsApp\Contracts\WhatsAppSender;

class SendCoinPurchaseReceipt implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly WhatsAppSender $whatsApp,
    ) {}

    public function handle(CoinPackPurchased $event): void
    {
        $vendor = $event->purchase->vendor;
        $to = $vendor?->whatsapp_number;

        if ($to === null || $to === '') {
            return;
        }

        $purchase = $event->purchase;

        $bonus = $purchase->bonus_coins > 0
            ? sprintf(' (%d + %d bonus)', $purchase->coins, $purchase->bonus_coins)
            : '';

        $this->whatsApp->sendText($to, sprintf(
            'Payment confirmed — %s coins%s added to your Jiidaa wallet for %s. New balance: %s coins.',
            number_format($purchase->totalCoins()),
            $bonus,
            '₦'.number_format($purchase->price),
            number_format($event->newBalance),
        ));
    }
}
