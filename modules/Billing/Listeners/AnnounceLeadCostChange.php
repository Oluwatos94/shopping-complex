<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\InteractsWithQueue;
use ModulesShoppingComplex\Billing\Events\CategoryLeadCostChanged;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\Data\VendorUpdate;
use ModulesShoppingComplex\Notifications\Jobs\SendVendorReminder;

class AnnounceLeadCostChange implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CategoryLeadCostChanged $event): void
    {
        $update = new VendorUpdate(
            subject: 'Lead pricing update',
            body: sprintf(
                "The cost to receive a customer lead in %s has changed from %d to %d coins, effective now.\n\n"
                .'This is the number of coins deducted from your wallet each time a new customer contacts you.',
                $event->category->name,
                $event->previousCost,
                $event->newCost,
            ),
            data: ['action' => 'lead_cost_change', 'category_id' => $event->category->id],
        );

        User::query()
            ->where('role', 'vendor')
            ->where('category_id', $event->category->id)
            ->whereNull('lead_coin_cost_override')
            ->chunkById(200, function (Collection $vendors) use ($update): void {
                foreach ($vendors as $vendor) {
                    SendVendorReminder::dispatch($vendor, $update);
                }
            });
    }
}
