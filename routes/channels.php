<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Models\Conversation;
use ModulesShoppingComplex\Models\SupportConversation;
use ModulesShoppingComplex\Models\User;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Private user channel for notifications
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return (int) $user->id === $id;
});

// Private vendor channel for vendor-specific notifications
Broadcast::channel('vendors.{vendorId}', function (User $user, int $vendorId) {
    if ((int) $user->id !== $vendorId || $user->role !== 'vendor') {
        Log::warning('WebSocket auth failed: Invalid vendor access attempt', [
            'vendor_id' => $vendorId,
            'user_id' => $user->id,
            'user_role' => $user->role,
        ]);

        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});

// Private product channel for product updates
Broadcast::channel('products.{productId}', function (User $user, int $productId) {
    $cacheKey = "ws.auth.product.{$productId}.user.{$user->id}";

    return Cache::remember($cacheKey, 300, function () use ($user, $productId) {
        $product = Product::find($productId);

        if (! $product) {
            Log::warning('WebSocket auth failed: Product not found', [
                'product_id' => $productId,
                'user_id' => $user->id,
            ]);

            return false;
        }

        // Only the vendor who owns the product can broadcast
        if ((int) $user->id !== (int) $product->vendor_id || $user->role !== 'vendor') {
            return false;
        }

        return true;
    });
});

// Private conversation channel for real-time chat
Broadcast::channel('conversation.{conversationId}', function (User $user, int $conversationId) {
    $cacheKey = "ws.auth.conversation.{$conversationId}.user.{$user->id}";

    return Cache::remember($cacheKey, 600, function () use ($user, $conversationId) {
        $conversation = Conversation::find($conversationId);

        if (! $conversation) {
            Log::warning('WebSocket auth failed: Conversation not found', [
                'conversation_id' => $conversationId,
                'user_id' => $user->id,
            ]);

            return false;
        }

        // Only participants can listen to the conversation
        if (! $conversation->hasParticipant($user->id)) {
            Log::warning('WebSocket auth failed: User not participant in conversation', [
                'conversation_id' => $conversationId,
                'user_id' => $user->id,
            ]);

            return false;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    });
});

// Presence channel for online vendors
Broadcast::channel('online-vendors', function (User $user) {
    if ($user->role !== 'vendor') {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});

// Private support conversation channel (customer widget + support agents)
Broadcast::channel('support-conversation.{conversationId}', function (User $user, int $conversationId) {
    $conversation = SupportConversation::find($conversationId);

    if (! $conversation) {
        Log::warning('WebSocket auth failed: Support conversation not found', [
            'support_conversation_id' => $conversationId,
            'user_id' => $user->id,
        ]);

        return false;
    }

    if ($conversation->user_id !== $user->id && $user->role !== 'admin') {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});

// Presence channel for customer support
Broadcast::channel('customer-support', function (User $user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'role' => $user->role,
    ];
});
