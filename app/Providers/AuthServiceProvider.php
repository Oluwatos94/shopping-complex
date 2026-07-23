<?php

declare(strict_types=1);

namespace App\Providers;

use App\Policies\ConversationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Catalog\Policies\ProductPolicy;
use ModulesShoppingComplex\Models\Conversation;
use ModulesShoppingComplex\Notifications\Models\Notification;
use ModulesShoppingComplex\Notifications\Policies\NotificationPolicy;
use ModulesShoppingComplex\Reviews\Models\Review;
use ModulesShoppingComplex\Reviews\Policies\ReviewPolicy;
use ModulesShoppingComplex\Support\Models\SupportConversation;
use ModulesShoppingComplex\Support\Policies\SupportConversationPolicy;

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
        Review::class => ReviewPolicy::class,
        SupportConversation::class => SupportConversationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
