<?php

namespace App\Providers;

use Anthropic\Client as AnthropicClient;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use ModulesShoppingComplex\Services\PaystackClient;
use ModulesShoppingComplex\Services\WhatsAppApiService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaystackClient::class, fn () => new PaystackClient(
            (string) (config('services.paystack.secret_key') ?? '')
        ));

        $this->app->singleton(WhatsAppApiService::class);

        $this->app->singleton(AnthropicClient::class, fn () => \Anthropic::factory()
            ->withApiKey((string) config('services.anthropic.api_key'))
            ->make()
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
