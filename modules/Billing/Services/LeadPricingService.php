<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use ModulesShoppingComplex\Catalog\Models\Category;
use ModulesShoppingComplex\Identity\Models\User;

final class LeadPricingService
{
    public function costFor(User $vendor): int
    {
        if ($vendor->lead_coin_cost_override !== null) {
            return max(1, $vendor->lead_coin_cost_override);
        }

        $categoryCost = $vendor->category_id === null
            ? null
            : Category::whereKey($vendor->category_id)->value('lead_coin_cost');

        return max(1, (int) ($categoryCost ?? config('billing.leads.default_cost', 5)));
    }
}
