<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Sales\PipelineService;
use Illuminate\Console\Command;

class SeedPipelineStages extends Command
{
    protected $signature = 'sales:seed-pipeline-stages {--tenant=}';

    protected $description = 'Seed default pipeline stages for tenant(s)';

    public function handle(PipelineService $pipeline): int
    {
        $query = Tenant::query();
        if ($this->option('tenant')) {
            $query->where('slug', $this->option('tenant'));
        }

        foreach ($query->get() as $tenant) {
            $pipeline->seedDefaultStagesForTenant($tenant->id);
            $this->info("Seeded pipeline stages for tenant: {$tenant->slug}");
        }

        return self::SUCCESS;
    }
}
