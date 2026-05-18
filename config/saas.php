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
    | Invoice-based billing (no card subscriptions)
    |--------------------------------------------------------------------------
    */
    'billing' => [
        'currency' => env('SAAS_BILLING_CURRENCY', 'GBP'),
        'default_payment_terms_days' => (int) env('SAAS_PAYMENT_TERMS_DAYS', 30),
        'grace_period_days' => (int) env('SAAS_BILLING_GRACE_DAYS', 7),
        'invoice_number_prefix' => env('SAAS_INVOICE_PREFIX', 'SAAS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan tiers (limits enforced in app; billed via manual/platform invoices)
    |--------------------------------------------------------------------------
    */
    'plans' => [
        'trial' => [
            'name' => 'Trial',
            'max_users' => 5,
            'max_branches' => 2,
            'trial_days' => 14,
            'monthly_amount' => 0,
        ],
        'starter' => [
            'name' => 'Starter',
            'max_users' => 10,
            'max_branches' => 3,
            'monthly_amount' => 99,
        ],
        'professional' => [
            'name' => 'Professional',
            'max_users' => 50,
            'max_branches' => 10,
            'monthly_amount' => 299,
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'max_users' => null,
            'max_branches' => null,
            'monthly_amount' => null, // custom quote
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
