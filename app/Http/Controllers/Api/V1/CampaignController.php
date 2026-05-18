<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignStep;
use App\Services\Auth\AuthorizationService;
use App\Services\Engagement\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly CampaignService $campaigns,
    ) {}

    public function index()
    {
        return response()->json([
            'status' => true,
            'data' => Campaign::with('steps')->orderByDesc('id')->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'campaigns.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'channel' => 'required|string',
            'segment_id' => 'nullable|integer',
            'steps' => 'nullable|array',
            'steps.*.template_id' => 'required_with:steps|integer',
            'steps.*.delay_hours' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $campaign = Campaign::create([
            'name' => $request->name,
            'channel' => $request->channel,
            'segment_id' => $request->segment_id,
            'template_id' => $request->template_id,
            'scheduled_at' => $request->scheduled_at,
            'created_by' => $request->user()->id,
        ]);

        foreach ($request->steps ?? [] as $i => $step) {
            CampaignStep::create([
                'campaign_id' => $campaign->id,
                'step_order' => $i + 1,
                'delay_hours' => $step['delay_hours'] ?? ($i === 0 ? 0 : 24),
                'template_id' => $step['template_id'],
            ]);
        }

        if ($request->segment_id) {
            $this->campaigns->buildRecipientsFromSegment($campaign);
        }

        return response()->json(['status' => true, 'data' => $campaign->load('steps')], 201);
    }

    public function launch(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'campaigns.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $campaign = $this->campaigns->launch(Campaign::findOrFail($id));

        return response()->json(['status' => true, 'data' => $campaign]);
    }
}
