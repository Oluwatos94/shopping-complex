<?php

declare(strict_types=1);

namespace App\Providers;

use App\Policies\ConversationPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\SupportConversationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use ModulesShoppingComplex\Models\Conversation;
use ModulesShoppingComplex\Models\Notification;
use ModulesShoppingComplex\Models\Product;
use ModulesShoppingComplex\Models\Review;
use ModulesShoppingComplex\Models\SupportConversation;

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
