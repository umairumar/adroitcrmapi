<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CrmLead;
use App\Models\PipelineStage;
use App\Services\Auth\AuthorizationService;
use App\Services\Sales\LeadAssignmentService;
use App\Services\Sales\PipelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SalesPipelineController extends Controller
{
    public function __construct(
        private readonly PipelineService $pipeline,
        private readonly AuthorizationService $authz,
        private readonly LeadAssignmentService $assignment,
    ) {}

    public function kanban(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'pipeline.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        return response()->json([
            'status' => true,
            'data' => $this->pipeline->kanbanBoard($request->user()),
        ]);
    }

    public function funnel(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'pipeline.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        return response()->json([
            'status' => true,
            'data' => $this->pipeline->funnelStats($request->user()),
        ]);
    }

    public function slaBreaches(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'pipeline.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        return response()->json([
            'status' => true,
            'data' => $this->pipeline->slaBreaches($request->user())->values(),
        ]);
    }

    public function moveStage(Request $request, int $leadId)
    {
        if (! $this->authz->hasPermission($request->user(), 'leads.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'pipeline_stage_id' => 'required_without:stage_slug|exists:pipeline_stages,id',
            'stage_slug' => 'required_without:pipeline_stage_id|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $lead = CrmLead::findOrFail($leadId);
        $this->authz->assertLeadAccessible($request->user(), $lead);

        $stage = $request->filled('pipeline_stage_id')
            ? PipelineStage::findOrFail($request->pipeline_stage_id)
            : PipelineStage::where('slug', $request->stage_slug)->firstOrFail();

        $lead = $this->pipeline->moveLeadToStage($lead, $stage, $request->user(), $request->notes);

        return response()->json([
            'status' => true,
            'message' => 'Lead moved to ' . $stage->name,
            'data' => $lead,
        ]);
    }

    public function history(Request $request, int $leadId)
    {
        $lead = CrmLead::findOrFail($leadId);
        $this->authz->assertLeadAccessible($request->user(), $lead);

        $history = \Illuminate\Support\Facades\DB::table('pipeline_stage_history')
            ->leftJoin('pipeline_stages as fs', 'fs.id', '=', 'pipeline_stage_history.from_stage_id')
            ->leftJoin('pipeline_stages as ts', 'ts.id', '=', 'pipeline_stage_history.to_stage_id')
            ->where('pipeline_stage_history.lead_id', $leadId)
            ->orderByDesc('pipeline_stage_history.created_at')
            ->select([
                'pipeline_stage_history.*',
                'fs.name as from_stage_name',
                'ts.name as to_stage_name',
            ])
            ->get();

        return response()->json(['status' => true, 'data' => $history]);
    }

    public function sources()
    {
        return response()->json([
            'status' => true,
            'data' => config('sales.sources', []),
        ]);
    }

    public function autoAssign(Request $request, int $leadId)
    {
        if (! $this->authz->hasPermission($request->user(), 'leads.assign')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $lead = CrmLead::findOrFail($leadId);
        $this->authz->assertLeadAccessible($request->user(), $lead);

        $agentId = $this->assignment->autoAssign($lead);

        return response()->json([
            'status' => true,
            'message' => $agentId ? 'Lead assigned' : 'No matching rule found',
            'agent_id' => $agentId,
            'data' => $lead->fresh(),
        ]);
    }
}
