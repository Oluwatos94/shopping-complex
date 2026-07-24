<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use ModulesShoppingComplex\Billing\Events\SubscriptionPaymentSucceeded;
use ModulesShoppingComplex\Billing\Events\SubscriptionRenewalFailed;
use ModulesShoppingComplex\Billing\Listeners\SendRenewalFailedWhatsApp;
use ModulesShoppingComplex\Billing\Listeners\SendSubscriptionPaymentWhatsApp;
use ModulesShoppingComplex\Billing\Payments\PaymentProviderManager;
use ModulesShoppingComplex\Billing\Payments\PaystackProvider;
use ModulesShoppingComplex\Billing\Payments\Stellar\AnchorClient;
use ModulesShoppingComplex\Billing\Payments\Stellar\Contracts\RecurringCharger;
use ModulesShoppingComplex\Billing\Payments\Stellar\SorobanCharger;
use ModulesShoppingComplex\Billing\Payments\Stellar\StellarDepositService;
use ModulesShoppingComplex\Billing\Payments\Stellar\StellarProvider;
use ModulesShoppingComplex\Billing\Payments\Stellar\StellarSigner;
use ModulesShoppingComplex\Billing\Payments\Stellar\StellarTestnetFunder;
use ModulesShoppingComplex\Billing\Payments\Stellar\StellarWalletService;
use ModulesShoppingComplex\Billing\Services\PaystackClient;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Discovery\Services\GeoLocationService;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Shared\Ai\ClaudeClient;
use ModulesShoppingComplex\Shared\Ai\GeminiClient;
use ModulesShoppingComplex\Shared\Contracts\AiChatClient;
use ModulesShoppingComplex\WhatsApp\Contracts\WhatsAppSender;
use ModulesShoppingComplex\WhatsApp\Services\WhatsAppApiService;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\StellarSDK;

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
            $app->make(StellarProvider::class),
        ]));

        $this->registerStellar();

        $this->app->singleton(WhatsAppApiService::class);
        $this->app->bind(WhatsAppSender::class, WhatsAppApiService::class);

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

        $this->app->singleton(GeoLocationService::class, fn () => new GeoLocationService(
            apiKey: (string) config('services.google_maps.key'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Existing media.model_type rows store the pre-restructure FQCNs. Map them
        // so polymorphic lookups keep matching after models moved to domain modules.
        Relation::morphMap([
            'ModulesShoppingComplex\Models\User' => User::class,
            'ModulesShoppingComplex\Models\Product' => Product::class,
        ]);

        Event::listen(SubscriptionPaymentSucceeded::class, SendSubscriptionPaymentWhatsApp::class);
        Event::listen(SubscriptionRenewalFailed::class, SendRenewalFailedWhatsApp::class);
    }

    /**
     * Wire the Stellar SDK + anchor clients from config/services.php.
     */
    private function registerStellar(): void
    {
        $this->app->singleton(Network::class, fn () => config('services.stellar.network') === 'public'
            ? Network::public()
            : Network::testnet());

        $this->app->singleton(StellarSDK::class, fn () => new StellarSDK(
            (string) config('services.stellar.horizon_url')
        ));

        $this->app->singleton(AnchorClient::class, fn ($app) => new AnchorClient(
            baseUrl: (string) config('services.stellar.anchor_base_url'),
            sep10SigningKey: (string) config('services.stellar.anchor_sep10_signing_key'),
            homeDomain: (string) config('services.stellar.anchor_home_domain'),
            network: $app->make(Network::class),
            ngncAssetCode: (string) config('services.stellar.ngnc_asset_code'),
            ngncIssuer: (string) config('services.stellar.ngnc_issuer'),
        ));

        $this->app->singleton(StellarWalletService::class, fn ($app) => new StellarWalletService(
            sdk: $app->make(StellarSDK::class),
            network: $app->make(Network::class),
            networkName: (string) config('services.stellar.network'),
            ngncAssetCode: (string) config('services.stellar.ngnc_asset_code'),
            ngncIssuer: (string) config('services.stellar.ngnc_issuer'),
        ));

        $this->app->singleton(StellarDepositService::class, fn ($app) => new StellarDepositService(
            anchor: $app->make(AnchorClient::class),
            platformSigner: new StellarSigner(
                (string) config('services.stellar.platform_wallet_public'),
                (string) config('services.stellar.platform_wallet_secret'),
            ),
            ngncAssetCode: (string) config('services.stellar.ngnc_asset_code'),
        ));

        $this->app->singleton(SorobanServer::class, fn () => new SorobanServer(
            (string) config('services.stellar.soroban_rpc_url')
        ));

        $this->app->singleton(RecurringCharger::class, fn ($app) => new SorobanCharger(
            soroban: $app->make(SorobanServer::class),
            network: $app->make(Network::class),
            platformWalletPublic: (string) config('services.stellar.platform_wallet_public'),
            ngncSac: (string) config('services.stellar.ngnc_sac'),
        ));

        $this->app->singleton(StellarTestnetFunder::class, fn ($app) => new StellarTestnetFunder(
            sdk: $app->make(StellarSDK::class),
            network: $app->make(Network::class),
            platformSigner: new StellarSigner(
                (string) config('services.stellar.platform_wallet_public'),
                (string) config('services.stellar.platform_wallet_secret'),
            ),
            ngncAssetCode: (string) config('services.stellar.ngnc_asset_code'),
            ngncIssuer: (string) config('services.stellar.ngnc_issuer'),
        ));
    }
}
