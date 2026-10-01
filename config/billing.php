<?php

return [

    'leads' => [
        'default_cost' => (int) env('LEAD_DEFAULT_COST', 5),

        'low_balance_leads' => (int) env('LEAD_LOW_BALANCE_LEADS', 3),

        // How a lead becomes chargeable:
        //  - 'click'  (legacy): the buyer's tap on a contact link is billed immediately.
        //  - 'accept': the buyer's tap only sends a request; the vendor is charged when
        //              they accept it (WhatsApp button or the Leads page). Requires the
        //              approved `lead_request` WhatsApp template and PLATFORM_WHATSAPP_NUMBER.
        'mode' => env('LEAD_BILLING_MODE', 'click'),

        // A pending request expires (no charge) if the vendor does not answer in time.
        // Keep this under 24h: the buyer is told about the outcome with a free-form
        // message, which WhatsApp only allows inside the 24h customer-service window.
        'accept_window_hours' => (int) env('LEAD_ACCEPT_WINDOW_HOURS', 12),

        // A single buyer cannot have more than this many open requests at once, so one
        // phone number cannot flood vendors with request templates.
        'max_pending_per_buyer' => (int) env('LEAD_MAX_PENDING_PER_BUYER', 5),
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

        'naira_value' => (int) env('COIN_NAIRA_VALUE', 50),

        // The coin stays a stable ₦50 unit of account. Discounts are delivered
        // as bonus coins, never as a cheaper coin, so 'coins' is always price/50
        // and 'bonus_coins' is what the pack throws in on top.
        'free_tier_coins' => (int) env('COIN_FREE_TIER_COINS', 30),

        // Testnet anti-abuse: block a fresh Stellar coin purchase while the vendor still holds
        // this many coins, so free testnet coins can't be restacked. Raise/remove for mainnet.
        'repurchase_ceiling' => (int) env('COIN_REPURCHASE_CEILING', 100),

        'packs' => [
            'starter' => ['name' => 'Starter', 'price' => 5000, 'coins' => 100, 'bonus_coins' => 0],
            'growth' => ['name' => 'Growth', 'price' => 20000, 'coins' => 400, 'bonus_coins' => 40],
            'scale' => ['name' => 'Scale', 'price' => 50000, 'coins' => 1000, 'bonus_coins' => 150],
        ],
    ],

];
