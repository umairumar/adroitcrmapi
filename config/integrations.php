<?php

return [

    'integration_statuses' => ['inactive', 'active', 'error', 'pending'],

    'sync_types' => ['search', 'import', 'booking', 'availability'],

    'marketplace' => [
        'key_prefix' => env('MARKETPLACE_API_KEY_PREFIX', 'adc_'),
        'default_rate_limit' => (int) env('MARKETPLACE_RATE_LIMIT', 60),
        'key_ttl_days' => (int) env('MARKETPLACE_KEY_TTL_DAYS', 0), // 0 = no expiry
    ],

    'providers' => [
        'amadeus' => [
            'name' => 'Amadeus GDS',
            'type' => 'gds',
            'credential_fields' => ['client_id', 'client_secret', 'environment'],
        ],
        'sabre' => [
            'name' => 'Sabre GDS',
            'type' => 'gds',
            'credential_fields' => ['username', 'password', 'pcc', 'environment'],
        ],
        'hotelbeds' => [
            'name' => 'Hotelbeds',
            'type' => 'ota_hotel',
            'credential_fields' => ['api_key', 'secret'],
        ],
        'booking_com' => [
            'name' => 'Booking.com Partner',
            'type' => 'ota_hotel',
            'credential_fields' => ['affiliate_id', 'api_key'],
        ],
        'expedia' => [
            'name' => 'Expedia Partner',
            'type' => 'ota_hotel',
            'credential_fields' => ['api_key', 'cid'],
        ],
    ],

    'default_branding' => [
        'app_name' => 'Adroit Travel CRM',
        'primary_color' => '#0f766e',
        'secondary_color' => '#134e4a',
        'accent_color' => '#14b8a6',
    ],

];
