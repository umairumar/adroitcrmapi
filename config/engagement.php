<?php

return [

    'channels' => ['email', 'whatsapp', 'sms', 'messenger', 'web_chat'],

    'message_statuses' => ['queued', 'sent', 'delivered', 'read', 'failed'],

    'campaign_statuses' => ['draft', 'scheduled', 'running', 'paused', 'completed'],

    'portal' => [
        'token_ttl_hours' => (int) env('B2C_PORTAL_TOKEN_TTL', 72),
        'magic_link_path' => '/portal/auth',
    ],

    'providers' => [
        'email' => env('ENGAGEMENT_EMAIL_DRIVER', 'smtp'),
        'whatsapp' => env('WHATSAPP_API_URL'),
        'sms' => env('SMS_API_URL'),
    ],

    'webhook_secret' => env('ENGAGEMENT_WEBHOOK_SECRET'),

];
