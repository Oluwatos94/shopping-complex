<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Notifications\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\Data\VendorUpdate;
use ModulesShoppingComplex\Notifications\Services\VendorUpdateService;

class SendVendorReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly User $vendor,
        public readonly VendorUpdate $update,
    ) {}

    public function handle(VendorUpdateService $vendorUpdates): void
    {
        $vendorUpdates->send($this->vendor, $this->update);
    }
}
