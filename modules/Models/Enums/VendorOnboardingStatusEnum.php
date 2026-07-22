<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models\Enums;

use ModulesShoppingComplex\Shared\Support\EnumToArray;

enum VendorOnboardingStatusEnum: string
{
    use EnumToArray;

    case DRAFT = 'draft';
    case PENDING_REVIEW = 'pending_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
