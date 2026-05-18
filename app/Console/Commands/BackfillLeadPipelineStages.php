<?php

namespace App\Console\Commands;

use App\Models\CrmLead;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Services\Sales\PipelineService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class BackfillLeadPipelineStages extends Command
{
    protected $signature = 'sales:backfill-pipeline-stages {--tenant=} {--dry-run}';

    protected $description = 'Map legacy lead status to pipeline_stage_id for existing leads';

    public function handle(PipelineService $pipeline): int
    {
        if (! Schema::hasColumn('crm_leads', 'pipeline_stage_id')) {
            $this->error('Run sales migrations first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $tenantQuery = Tenant::query();
        if ($this->option('tenant')) {
            $tenantQuery->where('slug', $this->option('tenant'));
        }

        $updated = 0;

        foreach ($tenantQuery->get() as $tenant) {
            $pipeline->seedDefaultStagesForTenant($tenant->id);

            $stagesByLegacy = PipelineStage::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereNotNull('legacy_status')
                ->get()
                ->keyBy('legacy_status');

            $leads = CrmLead::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereNull('pipeline_stage_id')
                ->get();

            foreach ($leads as $lead) {
                $stage = $stagesByLegacy->get($lead->status)
                    ?? PipelineStage::withoutGlobalScopes()
                        ->where('tenant_id', $tenant->id)
                        ->where('slug', 'new')
                        ->first();

                if (! $stage) {
                    continue;
                }

                if (! $dryRun) {
                    $lead->update([
                        'pipeline_stage_id' => $stage->id,
                        'stage_entered_at' => $lead->cdate ?? now(),
                    ]);
                }

                $updated++;
            }
        }

        $this->info(($dryRun ? 'Would update' : 'Updated') . " {$updated} lead(s).");

        return self::SUCCESS;
    }
}
