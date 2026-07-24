<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Reviews\Events;

use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\Events\BaseNotificationEvent;

class ReviewReceivedEvent extends BaseNotificationEvent
{
    public function __construct(
        User $vendor,
        public readonly User $customer,
        public readonly int $rating,
        public readonly ?string $reviewTitle = null
    ) {
        $message = "{$customer->name} left you a {$rating}-star review";

        parent::__construct(
            recipient: $vendor,
            message: $message,
            data: [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'rating' => $rating,
                'review_title' => $reviewTitle,
            ]
        );
    }

    public function getNotificationType(): string
    {
        return 'review_received';
    }
}
