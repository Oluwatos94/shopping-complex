<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Enums;

/**
 * Outcome of a vendor trying to accept a pending lead.
 */
enum LeadAcceptStatusEnum: string
{
    case ACCEPTED = 'accepted';

    /** Already accepted earlier; nothing charged again. */
    case ALREADY_ACCEPTED = 'already_accepted';

    /** Declined or expired; it can no longer be accepted. */
    case CLOSED = 'closed';

    /** Not enough coins; the request stays pending until it expires. */
    case INSUFFICIENT_BALANCE = 'insufficient_balance';

    /** Accepting would exceed the vendor's own daily coin cap. */
    case DAILY_CAP = 'daily_cap';
}
