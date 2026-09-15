<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ModulesShoppingComplex\Catalog\Models\Category;

class CategoryLeadCostChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Category $category,
        public readonly int $previousCost,
        public readonly int $newCost,
    ) {}
}
