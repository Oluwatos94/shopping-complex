<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Billing\Repositories\CoinLedgerRepository;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Identity\Models\User;

class ExpireCoins implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 3600;

    public function handle(CoinLedgerRepository $ledger, CoinWalletService $wallets): void
    {
        $asOf = now();

        $ledger->vendorsWithExpiredLots($asOf)->each(function (int $vendorId) use ($wallets, $asOf): void {
            $vendor = User::find($vendorId);

            if ($vendor === null) {
                return;
            }

            $expired = $wallets->expire($vendor, $asOf);

            if ($expired > 0) {
                Log::info('Expired vendor coins', ['vendor_id' => $vendorId, 'coins' => $expired]);
            }
        });
    }
}
