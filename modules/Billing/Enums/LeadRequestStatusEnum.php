<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Enums;

/**
 * Outcome of a buyer asking to be introduced to a vendor (accept mode).
 */
enum LeadRequestStatusEnum: string
{
    /** A new pending request was created and the vendor is being asked to accept it. */
    case REQUESTED = 'requested';

    /** The same buyer already has an open request with this vendor. */
    case ALREADY_PENDING = 'already_pending';

    /** The vendor already accepted this buyer inside the 30-day window; repeat contact is free. */
    case ALREADY_ACCEPTED = 'already_accepted';

    /** The vendor declined this buyer inside the 30-day window. */
    case DECLINED = 'declined';

    /** The buyer has too many open requests. */
    case TOO_MANY_PENDING = 'too_many_pending';

    /** The buyer is the vendor. */
    case SELF = 'self';

    /** The vendor has no usable WhatsApp number. */
    case NO_CONTACT = 'no_contact';

    /** The reference in the buyer's message does not match a contact link. */
    case UNKNOWN_REFERENCE = 'unknown_reference';
}
