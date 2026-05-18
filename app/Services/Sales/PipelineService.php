<?php

namespace App\Services\Sales;

use App\Models\CrmLead;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PipelineService
{
    public function __construct(
        private readonly AuthorizationService $authz,
    ) {}

    public function seedDefaultStagesForTenant(int $tenantId): void
    {
        foreach (config('sales.default_stages', []) as $stage) {
            PipelineStage::withoutGlobalScopes()->firstOrCreate(
                ['tenant_id' => $tenantId, 'slug' => $stage['slug']],
                [
                    'name' => $stage['name'],
                    'sort_order' => $stage['sort_order'],
                    'color' => $stage['color'] ?? null,
                    'legacy_status' => $stage['legacy_status'] ?? null,
                    'sla_hours' => $stage['sla_hours'] ?? null,
                    'is_won' => $stage['is_won'] ?? false,
                    'is_lost' => $stage['is_lost'] ?? false,
                    'is_active' => true,
                ]
            );
        }
    }

    public function defaultStageForTenant(int $tenantId): ?PipelineStage
    {
        return PipelineStage::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('slug', 'new')
            ->first();
    }

    public function moveLeadToStage(
        CrmLead $lead,
        PipelineStage $stage,
        ?User $user = null,
        ?string $notes = null,
    ): CrmLead {
        $fromStageId = $lead->pipeline_stage_id;

        return DB::transaction(function () use ($lead, $stage, $fromStageId, $user, $notes) {
            $lead->update([
                'pipeline_stage_id' => $stage->id,
                'status' => $stage->legacy_status ?? $lead->status,
                'stage_entered_at' => now(),
                'mdate' => now(),
                'mby' => $user?->id ?? $lead->mby,
            ]);

            DB::table('pipeline_stage_history')->insert([
                'tenant_id' => $lead->tenant_id,
                'lead_id' => $lead->id,
                'from_stage_id' => $fromStageId,
                'to_stage_id' => $stage->id,
                'user_id' => $user?->id,
                'notes' => $notes,
                'created_at' => now(),
            ]);

            return $lead->fresh(['pipelineStage']);
        });
    }

    public function kanbanBoard(User $user): array
    {
        $stages = PipelineStage::orderBy('sort_order')->get();
        $columns = [];

        foreach ($stages as $stage) {
            $query = $this->authz->scopeLeads(
                CrmLead::query()->where('pipeline_stage_id', $stage->id),
                $user
            );

            $leads = $query
                ->select([
                    'id', 'name', 'email', 'phone', 'agent', 'source', 'estimated_value',
                    'stage_entered_at', 'assigned_at', 'pipeline_stage_id', 'status', 'cdate',
                ])
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->map(fn ($lead) => $this->formatKanbanCard($lead, $stage));

            $columns[] = [
                'stage' => $stage,
                'leads' => $leads,
                'count' => $leads->count(),
                'total_value' => $leads->sum('estimated_value'),
            ];
        }

        return $columns;
    }

    public function funnelStats(User $user): array
    {
        $stages = PipelineStage::orderBy('sort_order')->get();

        return $stages->map(function (PipelineStage $stage) use ($user) {
            $q = $this->authz->scopeLeads(
                CrmLead::query()->where('pipeline_stage_id', $stage->id),
                $user
            );

            return [
                'stage_id' => $stage->id,
                'slug' => $stage->slug,
                'name' => $stage->name,
                'color' => $stage->color,
                'count' => (clone $q)->count(),
                'total_value' => (float) (clone $q)->sum('estimated_value'),
            ];
        })->values()->all();
    }

    public function slaBreaches(User $user): Collection
    {
        $stages = PipelineStage::whereNotNull('sla_hours')->where('is_active', true)->get();
        $breaches = collect();

        foreach ($stages as $stage) {
            $deadline = Carbon::now()->subHours($stage->sla_hours);

            $leads = $this->authz->scopeLeads(
                CrmLead::query()
                    ->where('pipeline_stage_id', $stage->id)
                    ->where('stage_entered_at', '<', $deadline),
                $user
            )->limit(50)->get();

            foreach ($leads as $lead) {
                $breaches->push([
                    'lead' => $lead,
                    'stage' => $stage,
                    'hours_in_stage' => Carbon::parse($lead->stage_entered_at)->diffInHours(now()),
                    'sla_hours' => $stage->sla_hours,
                ]);
            }
        }

        return $breaches;
    }

    private function formatKanbanCard(CrmLead $lead, PipelineStage $stage): array
    {
        $slaBreached = false;
        if ($stage->sla_hours && $lead->stage_entered_at) {
            $slaBreached = Carbon::parse($lead->stage_entered_at)
                ->addHours($stage->sla_hours)
                ->isPast();
        }

        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'agent' => $lead->agent,
            'source' => $lead->source,
            'estimated_value' => (float) ($lead->estimated_value ?? 0),
            'stage_entered_at' => $lead->stage_entered_at,
            'sla_breached' => $slaBreached,
            'status' => $lead->status,
        ];
    }
}
