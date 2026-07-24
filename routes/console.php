<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use ModulesShoppingComplex\Billing\Jobs\ExpireVendorSubscriptions;
use ModulesShoppingComplex\Billing\Jobs\RenewVendorSubscriptions;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Renew due Stellar subscriptions first so a successful charge pre-empts the expiry sweep below.
Schedule::job(RenewVendorSubscriptions::class)->dailyAt('00:00');
Schedule::job(ExpireVendorSubscriptions::class)->dailyAt('00:05');
