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
    | A referral counts once the referred vendor has registered and verified
    | their email. Set this above zero to also require the referred business
    | to list that many products before the referral counts.
    |
    */

    'min_products' => env('REFERRAL_MIN_PRODUCTS', 0),

];
