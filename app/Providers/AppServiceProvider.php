<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ModulesShoppingComplex\Services\PaystackClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaystackClient::class, fn () => new PaystackClient(
            config('services.paystack.secret_key', '')
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
