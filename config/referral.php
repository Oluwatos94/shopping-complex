<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Referral Settings
    |--------------------------------------------------------------------------
    |
    | Referral codes are permanent — every vendor keeps one and shareable links
    | stay valid indefinitely. Codes are drawn from an unambiguous alphabet so
    | they survive being read aloud or retyped from a chat message.
    |
    */

    'code_length' => env('REFERRAL_CODE_LENGTH', 8),

    'share_path' => env('REFERRAL_SHARE_PATH', '/register'),

    /*
    |--------------------------------------------------------------------------
    | What Counts As A Referral
    |--------------------------------------------------------------------------
    |
    | Signing someone up is not the win — a vendor who lists nothing is not a
    | vendor. A referral is only counted once the referred business has listed
    | this many products, so a referrer's number reflects businesses actually
    | trading rather than dormant accounts.
    |
    */

    'min_products' => env('REFERRAL_MIN_PRODUCTS', 5),

];
