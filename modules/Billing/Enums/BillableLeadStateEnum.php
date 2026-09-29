<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Enums;

use ModulesShoppingComplex\Shared\Support\EnumToArray;

enum BillableLeadStateEnum: string
{
    use EnumToArray;

    case CHARGED = 'charged';
    case UNBILLED = 'unbilled';

    /** Accept mode: the request was sent to the vendor and waits for an answer. Nothing charged yet. */
    case PENDING = 'pending';

    /** Accept mode: the vendor turned the request down. Never charged. */
    case DECLINED = 'declined';

    /** Accept mode: the vendor did not answer before expires_at. Never charged. */
    case EXPIRED = 'expired';
}
