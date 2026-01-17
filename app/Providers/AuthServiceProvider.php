<?php

declare(strict_types=1);

namespace App\Providers;

use App\Policies\ConversationPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\ProductPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use ModulesShoppingComplex\Models\Conversation;
use ModulesShoppingComplex\Models\Notification;
use ModulesShoppingComplex\Models\Product;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Product::class => ProductPolicy::class,
        Notification::class => NotificationPolicy::class,
        Conversation::class => ConversationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
