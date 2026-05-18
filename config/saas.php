<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default tenant for legacy single-tenant deployments
    |--------------------------------------------------------------------------
    */
    'legacy_tenant_slug' => env('SAAS_LEGACY_TENANT_SLUG', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Subscription plans (Stripe IDs wired in Phase 0.5+)
    |--------------------------------------------------------------------------
    */
    'plans' => [
        'trial' => [
            'name' => 'Trial',
            'max_users' => 5,
            'max_branches' => 2,
            'trial_days' => 14,
        ],
        'starter' => [
            'name' => 'Starter',
            'max_users' => 10,
            'max_branches' => 3,
        ],
        'professional' => [
            'name' => 'Professional',
            'max_users' => 50,
            'max_branches' => 10,
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'max_users' => null,
            'max_branches' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Maps legacy utype values to SaaS role slugs
    |--------------------------------------------------------------------------
    */
    'utype_role_map' => [
        'sadmin' => 'platform_admin',
        'cadmin' => 'tenant_admin',
        'agent' => 'agent',
        'Accountant' => 'accountant',
    ],

];
