<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Events;

use ModulesShoppingComplex\Models\User;

class ProductUpdatedEvent extends BaseNotificationEvent
{
    /**
     * @param  string  $updateType  One of: 'price_change', 'back_in_stock', 'new_images'
     */
    public function __construct(
        User $recipient,
        public readonly int $productId,
        public readonly string $productName,
        public readonly string $updateType
    ) {
        $messages = [
            'price_change' => "Price updated for {$productName}",
            'back_in_stock' => "{$productName} is back in stock!",
            'new_images' => "New images added to {$productName}",
        ];

        parent::__construct(
            recipient: $recipient,
            message: $messages[$updateType] ?? "Update on {$productName}",
            data: [
                'product_id' => $productId,
                'product_name' => $productName,
                'update_type' => $updateType,
            ]
        );
    }

    public function getNotificationType(): string
    {
        return 'product_updated';
    }

    public function getGroupKey(): ?string
    {
        return "product_{$this->productId}_updates";
    }
}
