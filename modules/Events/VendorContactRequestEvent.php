<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Events;

use ModulesShoppingComplex\Models\User;

class VendorContactRequestEvent extends BaseNotificationEvent
{
    public function __construct(
        User $vendor,
        public readonly User $customer,
        public readonly ?string $productName = null
    ) {
        $message = $productName
            ? "{$customer->name} wants to inquire about {$productName}"
            : "{$customer->name} wants to contact you";

        parent::__construct(
            recipient: $vendor,
            message: $message,
            data: [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'product_name' => $productName,
            ]
        );
    }

    public function getNotificationType(): string
    {
        return 'vendor_contact_request';
    }

    public function getGroupKey(): ?string
    {
        return "contact_from_{$this->customer->id}";
    }
}
