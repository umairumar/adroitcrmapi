<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PipelineStage;
use App\Services\Auth\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PipelineStageController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
    ) {}

    public function index(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'pipeline.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $stages = PipelineStage::orderBy('sort_order')->get();

        return response()->json(['status' => true, 'data' => $stages]);
    }

    public function store(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'pipeline.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
            'color' => 'nullable|string|max:20',
            'legacy_status' => 'nullable|string|max:50',
            'sla_hours' => 'nullable|integer|min:1',
            'is_won' => 'boolean',
            'is_lost' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $slug = $request->slug ?: Str::slug($request->name);

        $stage = PipelineStage::create([
            'name' => $request->name,
            'slug' => $slug,
            'sort_order' => $request->sort_order ?? 99,
            'color' => $request->color,
            'legacy_status' => $request->legacy_status,
            'sla_hours' => $request->sla_hours,
            'is_won' => $request->boolean('is_won'),
            'is_lost' => $request->boolean('is_lost'),
        ]);

        return response()->json(['status' => true, 'data' => $stage], 201);
    }

    public function update(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'pipeline.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $stage = PipelineStage::findOrFail($id);
        $stage->update($request->only([
            'name', 'sort_order', 'color', 'legacy_status', 'sla_hours', 'is_won', 'is_lost', 'is_active',
        ]));

        return response()->json(['status' => true, 'data' => $stage]);
    }
}
