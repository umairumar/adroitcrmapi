<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\Auth\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeaveRequestController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
    ) {}

    public function index(Request $request)
    {
        $q = LeaveRequest::with('user')->orderByDesc('id');

        if (! $this->authz->isTenantAdmin($request->user())) {
            $q->where('user_id', $request->user()->id);
        }

        return response()->json(['status' => true, 'data' => $q->paginate(20)]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'leave_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $leave = LeaveRequest::create([
            'user_id' => $request->user()->id,
            'leave_type' => $request->leave_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
        ]);

        return response()->json(['status' => true, 'data' => $leave], 201);
    }

    public function review(Request $request, int $id)
    {
        if (! $this->authz->isTenantAdmin($request->user())) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $leave = LeaveRequest::findOrFail($id);
        $leave->update([
            'status' => $request->input('status', 'approved'),
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json(['status' => true, 'data' => $leave]);
    }
}
