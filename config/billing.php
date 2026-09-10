<?php

return [

    'leads' => [
        'coin_cost' => (int) env('LEAD_COIN_COST', 10),
    ],

    'coins' => [
        'expiry_months' => (int) env('COIN_EXPIRY_MONTHS', 12),
    ],

];
