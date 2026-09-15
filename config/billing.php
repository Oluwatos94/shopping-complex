<?php

return [

    'leads' => [
        'default_cost' => (int) env('LEAD_DEFAULT_COST', 5),

        'low_balance_leads' => (int) env('LEAD_LOW_BALANCE_LEADS', 3),
    ],

    'coins' => [
        'expiry_months' => (int) env('COIN_EXPIRY_MONTHS', 12),

        // The coin stays a stable ₦50 unit of account. Discounts are delivered
        // as bonus coins, never as a cheaper coin, so 'coins' is always price/50
        // and 'bonus_coins' is what the pack throws in on top.
        'packs' => [
            'starter' => ['name' => 'Starter', 'price' => 5000, 'coins' => 100, 'bonus_coins' => 0],
            'growth' => ['name' => 'Growth', 'price' => 20000, 'coins' => 400, 'bonus_coins' => 40],
            'scale' => ['name' => 'Scale', 'price' => 50000, 'coins' => 1000, 'bonus_coins' => 150],
        ],
    ],

];
