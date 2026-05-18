<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LeadAssignmentRule;
use App\Services\Auth\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeadAssignmentRuleController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
    ) {}

    public function index()
    {
        $rules = LeadAssignmentRule::orderBy('priority')->get();

        return response()->json(['status' => true, 'data' => $rules]);
    }

    public function store(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'pipeline.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'priority' => 'nullable|integer',
            'conditions' => 'required|array',
            'assign_to_user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $rule = LeadAssignmentRule::create([
            'name' => $request->name,
            'priority' => $request->priority ?? 100,
            'conditions' => $request->conditions,
            'assign_to_user_id' => $request->assign_to_user_id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json(['status' => true, 'data' => $rule], 201);
    }

    public function update(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'pipeline.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $rule = LeadAssignmentRule::findOrFail($id);
        $rule->update($request->only(['name', 'priority', 'conditions', 'assign_to_user_id', 'is_active']));

        return response()->json(['status' => true, 'data' => $rule]);
    }

    public function destroy(int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'pipeline.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        LeadAssignmentRule::findOrFail($id)->delete();

        return response()->json(['status' => true, 'message' => 'Rule deleted']);
    }
}
