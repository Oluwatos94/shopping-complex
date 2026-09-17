<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use ModulesShoppingComplex\Billing\Events\VendorLeadCharged;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\Repositories\NotificationRepository;

class WarnLowCoinBalance implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $backoff = 30;

    private const TYPE = 'low_balance';

    public function __construct(
        private readonly CoinWalletService $wallet,
        private readonly NotificationRepository $notifications,
    ) {}

    public function handle(VendorLeadCharged $event): void
    {
        $vendor = User::find($event->lead->vendor_id);

        if ($vendor === null) {
            return;
        }

        $threshold = $this->threshold();
        $balance = $this->wallet->balance($vendor);

        if ($balance >= $threshold) {
            return;
        }

        $this->notifications->createOrUpdateGrouped(
            userId: $vendor->id,
            type: self::TYPE,
            message: sprintf(
                'Your coin balance is low: %d coins left. Top up to keep claiming paid leads.',
                $balance,
            ),
            data: [
                'action' => 'top_up',
                'url' => route('vendor.coins.packs'),
                'balance' => $balance,
                'threshold' => $threshold,
            ],
            groupKey: self::TYPE.':'.$vendor->id,
        );
    }

    private function threshold(): int
    {
        $leads = (int) config('billing.leads.low_balance_leads', 3);
        $standardCost = (int) config('billing.leads.default_cost', 5);

        return $leads * $standardCost;
    }
}
