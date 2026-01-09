<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Models\Order;
use ModulesShoppingComplex\Models\Product;
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

// Private order channel - accessible by customer and vendor
Broadcast::channel('orders.{orderId}', function (User $user, int $orderId) {
    // Cache authorization result for 5 minutes
    $cacheKey = "ws.auth.order.{$orderId}.user.{$user->id}";

    return Cache::remember($cacheKey, 300, function () use ($user, $orderId) {
        $order = Order::find($orderId);

        if (! $order) {
            Log::warning('WebSocket auth failed: Order not found', [
                'order_id' => $orderId,
                'user_id' => $user->id,
            ]);

            return false;
        }

        // Allow customer who placed the order
        if ($user->id === $order->customer_id) {
            return ['id' => $user->id, 'name' => $user->name, 'role' => 'customer'];
        }

        // Allow vendor who owns products in the order - OPTIMIZED: No N+1 query
        $vendorIds = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.order_id', $orderId)
            ->pluck('products.vendor_id')
            ->unique();

        if ($vendorIds->contains($user->id)) {
            return ['id' => $user->id, 'name' => $user->name, 'role' => 'vendor'];
        }

        return false;
    });
});

// Private vendor channel for vendor-specific notifications
Broadcast::channel('vendors.{vendorId}', function (User $user, int $vendorId) {
    // SECURITY: Verify BOTH ID and role
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

// Private chat channel between vendor and customer
Broadcast::channel('chat.{userId1}.{userId2}', function (User $user, int $userId1, int $userId2) {
    // Cache authorization for 10 minutes
    $cacheKey = "ws.auth.chat.{$userId1}.{$userId2}.user.{$user->id}";

    return Cache::remember($cacheKey, 600, function () use ($user, $userId1, $userId2) {
        // Ensure user is a participant
        if ($user->id !== $userId1 && $user->id !== $userId2) {
            return false;
        }

        // Get the other user
        $otherUserId = $user->id === $userId1 ? $userId2 : $userId1;
        $otherUser = User::find($otherUserId);

        if (! $otherUser) {
            Log::warning('WebSocket auth failed: Chat partner not found', [
                'other_user_id' => $otherUserId,
                'user_id' => $user->id,
            ]);

            return false;
        }

        // SECURITY: Only allow vendor-customer chats
        if ($user->role === 'customer' && $otherUser->role === 'customer') {
            Log::warning('WebSocket auth failed: Customer-to-customer chat blocked', [
                'user_id' => $user->id,
                'other_user_id' => $otherUserId,
            ]);

            return false;
        }

        if ($user->role === 'vendor' && $otherUser->role === 'vendor') {
            Log::warning('WebSocket auth failed: Vendor-to-vendor chat blocked', [
                'user_id' => $user->id,
                'other_user_id' => $otherUserId,
            ]);

            return false;
        }

        // SECURITY: Verify they have a shared order (business relationship)
        $hasSharedOrder = DB::table('orders')
            ->where(function ($query) use ($user, $otherUser) {
                if ($user->role === 'customer') {
                    $query->where('customer_id', $user->id)
                        ->where('vendor_id', $otherUser->id);
                } else {
                    $query->where('vendor_id', $user->id)
                        ->where('customer_id', $otherUser->id);
                }
            })
            ->exists();

        if (! $hasSharedOrder) {
            Log::warning('WebSocket auth failed: No business relationship for chat', [
                'user_id' => $user->id,
                'other_user_id' => $otherUserId,
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
    // Only vendors can join this presence channel
    if ($user->role !== 'vendor') {
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
