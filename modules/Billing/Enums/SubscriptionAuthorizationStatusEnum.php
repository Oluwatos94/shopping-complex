<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Enums;

use ModulesShoppingComplex\Shared\Support\EnumToArray;

/**
 * Lifecycle of a vendor's business-layer consent to recurring (MPP) billing:
 * ACTIVE while the renewal job may charge, EXPIRED once past `valid_until`, REVOKED if the
 * vendor withdraws consent.
 */
enum SubscriptionAuthorizationStatusEnum: string
{
    use EnumToArray;

    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case REVOKED = 'revoked';
}
