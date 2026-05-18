<?php

namespace App\Console\Commands;

use App\Models\TenantIntegration;
use App\Services\Integrations\TenantIntegrationService;
use App\Services\Tenant\TenantContext;
use Illuminate\Console\Command;

class SyncIntegrations extends Command
{
    protected $signature = 'integrations:sync {--tenant=} {--provider=}';

    protected $description = 'Run import sync for active tenant GDS/OTA integrations';

    public function handle(TenantIntegrationService $service): int
    {
        $query = TenantIntegration::withoutGlobalScopes()
            ->where('status', 'active')
            ->with('provider');

        if ($this->option('tenant')) {
            $query->where('tenant_id', (int) $this->option('tenant'));
        }

        if ($this->option('provider')) {
            $query->whereHas('provider', fn ($q) => $q->where('slug', $this->option('provider')));
        }

        $integrations = $query->get();
        foreach ($integrations as $integration) {
            TenantContext::set($integration->tenant_id);
            TenantContext::disableBypass();

            $service->runSync($integration);
            $this->line("Synced integration #{$integration->id} ({$integration->provider?->slug})");
        }

        $this->info('Completed sync for ' . $integrations->count() . ' integration(s).');

        return self::SUCCESS;
    }
}
