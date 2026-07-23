<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\WhatsApp\Enums;

use ModulesShoppingComplex\Shared\Support\EnumToArray;

enum WhatsAppInteractionEventEnum: string
{
    use EnumToArray;

    case SEARCH = 'search';
    case VENDOR_VIEWED = 'vendor_viewed';
    case PRODUCT_CATALOGUE_VIEWED = 'product_catalogue_viewed';
    case CONTACT_REQUESTED = 'contact_requested';
    case NO_RESULTS = 'no_results';
}
