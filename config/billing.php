<?php

return [

    'leads' => [
        'default_cost' => (int) env('LEAD_DEFAULT_COST', 5),

        'low_balance_leads' => (int) env('LEAD_LOW_BALANCE_LEADS', 3),
    ],

    // Guards against a rival draining a vendor's balance by clicking their link.
    'guards' => [
        'velocity_buyer' => [
            'vendors' => (int) env('GUARD_VELOCITY_BUYER_VENDORS', 4),
            'minutes' => (int) env('GUARD_VELOCITY_BUYER_MINUTES', 5),
        ],
        'velocity_ip' => [
            'identities' => (int) env('GUARD_VELOCITY_IP_IDENTITIES', 6),
            'minutes' => (int) env('GUARD_VELOCITY_IP_MINUTES', 10),
        ],
        'rate_limit' => [
            'max' => (int) env('GUARD_REDIRECT_MAX', 5),
            'seconds' => (int) env('GUARD_REDIRECT_SECONDS', 60),
        ],
    ],

    'credits' => [
        'lookback_days' => (int) env('LEAD_CREDIT_LOOKBACK_DAYS', 35),
        'alert_rate' => (float) env('LEAD_CREDIT_ALERT_RATE', 0.05),
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
