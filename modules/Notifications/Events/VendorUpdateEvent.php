<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Notifications\Events;

class VendorUpdateEvent extends BaseNotificationEvent
{
    public function getNotificationType(): string
    {
        return 'vendor_update';
    }
}
