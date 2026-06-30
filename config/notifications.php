<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Types
    |--------------------------------------------------------------------------
    |
    | Define all available notification types with their default settings.
    | These are used for user preferences and email fallback configuration.
    |
    */
    'types' => [
        'message_received' => [
            'label' => 'Messages',
            'description' => 'When someone sends you a message',
            'default_email' => true,
            'default_push' => true,
            'groupable' => true,
        ],
        'vendor_contact_request' => [
            'label' => 'Contact Requests',
            'description' => 'When a customer wants to contact you',
            'default_email' => true,
            'default_push' => true,
            'groupable' => true,
        ],
        'review_received' => [
            'label' => 'Reviews',
            'description' => 'When a customer leaves you a review',
            'default_email' => true,
            'default_push' => true,
            'groupable' => false,
        ],
        'system_alert' => [
            'label' => 'System Alerts',
            'description' => 'Important system notifications',
            'default_email' => true,
            'default_push' => true,
            'groupable' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Fallback Settings
    |--------------------------------------------------------------------------
    |
    | Configure the email fallback behavior for missed notifications.
    |
    */
    'email_fallback_delay_minutes' => 5,

    /*
    |--------------------------------------------------------------------------
    | Grouping Settings
    |--------------------------------------------------------------------------
    |
    | Configure how notifications are grouped together.
    |
    */
    'grouping_window_hours' => 24,
];
