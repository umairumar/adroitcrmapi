<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Segment;
use App\Services\Auth\AuthorizationService;
use App\Services\Sales\SegmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SegmentController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly SegmentService $segments,
    ) {}

    public function index()
    {
        return response()->json([
            'status' => true,
            'data' => Segment::orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'contacts.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'entity_type' => 'required|in:contact,lead',
            'filters' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $segment = Segment::create([
            'name' => $request->name,
            'entity_type' => $request->entity_type,
            'filters' => $request->filters,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['status' => true, 'data' => $segment], 201);
    }

    public function preview(Request $request, int $id)
    {
        $segment = Segment::findOrFail($id);

        $query = $segment->entity_type === 'lead'
            ? $this->segments->queryLeads($segment)
            : $this->segments->queryContacts($segment);

        $results = $query->limit(100)->get();

        return response()->json([
            'status' => true,
            'count' => $results->count(),
            'data' => $results,
        ]);
    }
}
