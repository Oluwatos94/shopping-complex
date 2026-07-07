<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models\Enums;

use App\Traits\EnumToArray;

enum Sep24StatusEnum: string
{
    use EnumToArray;

    case INCOMPLETE = 'incomplete';
    case PENDING_USER_TRANSFER_START = 'pending_user_transfer_start';
    case PENDING_USER_TRANSFER_COMPLETE = 'pending_user_transfer_complete';
    case PENDING_EXTERNAL = 'pending_external';
    case PENDING_ANCHOR = 'pending_anchor';
    case PENDING_STELLAR = 'pending_stellar';
    case PENDING_TRUST = 'pending_trust';
    case PENDING_USER = 'pending_user';
    case COMPLETED = 'completed';
    case REFUNDED = 'refunded';
    case EXPIRED = 'expired';
    case ERROR = 'error';

    public function isFailure(): bool
    {
        return $this === self::ERROR || $this === self::EXPIRED || $this === self::REFUNDED;
    }
}
