<?php

namespace Database\Seeders;

use App\Models\IntegrationProvider;
use App\Models\MarketplaceApp;
use Illuminate\Database\Seeder;

class IntegrationsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('integrations.providers', []) as $slug => $def) {
            IntegrationProvider::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $def['name'],
                    'type' => $def['type'],
                    'description' => "Connect {$def['name']} for search and booking sync.",
                    'config_schema' => [
                        'fields' => collect($def['credential_fields'] ?? [])->map(fn ($f) => [
                            'key' => $f,
                            'label' => ucwords(str_replace('_', ' ', $f)),
                            'type' => str_contains($f, 'secret') || str_contains($f, 'password') ? 'password' : 'text',
                        ])->values()->all(),
                    ],
                    'is_active' => true,
                ]
            );
        }

        IntegrationProvider::whereIn('slug', ['sabre', 'booking_com'])->update(['is_active' => false]);

        $apps = [
            [
                'slug' => 'leads-api',
                'name' => 'Leads API',
                'description' => 'Read and create leads from external systems.',
                'category' => 'crm',
                'scopes' => ['leads.read', 'leads.write'],
            ],
            [
                'slug' => 'bookings-api',
                'name' => 'Bookings API',
                'description' => 'Access folder/booking data and payment status.',
                'category' => 'operations',
                'scopes' => ['folders.read', 'payments.read'],
            ],
            [
                'slug' => 'webhooks-out',
                'name' => 'Outbound Webhooks',
                'description' => 'Receive CRM events via signed webhooks (configure endpoints in app settings).',
                'category' => 'automation',
                'scopes' => ['webhooks.manage'],
            ],
            [
                'slug' => 'gds-bridge',
                'name' => 'GDS Bridge',
                'description' => 'Proxy flight/hotel search through configured GDS/OTA integrations.',
                'category' => 'travel',
                'scopes' => ['integrations.search'],
            ],
        ];

        foreach ($apps as $app) {
            MarketplaceApp::firstOrCreate(
                ['slug' => $app['slug']],
                array_merge($app, [
                    'rate_limit_per_minute' => (int) config('integrations.marketplace.default_rate_limit', 60),
                    'is_public' => true,
                    'is_active' => true,
                ])
            );
        }
    }
}
