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

];
