<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use ModulesShoppingComplex\Services\ClaudeClient;
use ModulesShoppingComplex\Services\Contracts\AiChatClient;
use ModulesShoppingComplex\Services\GeminiClient;
use ModulesShoppingComplex\Services\Payments\PaymentProviderManager;
use ModulesShoppingComplex\Services\Payments\PaystackProvider;
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

        $this->app->singleton(PaymentProviderManager::class, fn ($app) => new PaymentProviderManager([
            $app->make(PaystackProvider::class),
        ]));

        $this->app->singleton(WhatsAppApiService::class);

        $this->app->singleton(ClaudeClient::class, fn () => new ClaudeClient(
            apiKey: (string) config('services.claude.api_key'),
            model: (string) config('services.claude.model', 'claude-haiku-4-5-20251001'),
        ));

        $this->app->singleton(GeminiClient::class, fn () => new GeminiClient(
            apiKey: (string) config('services.gemini.api_key'),
            model: (string) config('services.gemini.model', 'gemini-2.5-flash'),
        ));

        $this->app->singleton(AiChatClient::class, fn ($app) => match (config('services.ai_bot.driver')) {
            'claude' => $app->make(ClaudeClient::class),
            default => $app->make(GeminiClient::class),
        });
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
