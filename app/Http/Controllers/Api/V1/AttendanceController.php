<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Services\Auth\AuthorizationService;
use App\Services\Operations\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendance,
        private readonly AuthorizationService $authz,
    ) {}

    public function clockIn(Request $request)
    {
        $user = $request->user();
        $record = $this->attendance->clockIn($user, $request->branch_id);

        return response()->json(['status' => true, 'data' => $record]);
    }

    public function clockOut(Request $request)
    {
        $record = $this->attendance->clockOut($request->user());

        return response()->json(['status' => true, 'data' => $record]);
    }

    public function myRecords(Request $request)
    {
        $records = AttendanceRecord::where('user_id', $request->user()->id)
            ->orderByDesc('work_date')
            ->limit(31)
            ->get();

        return response()->json(['status' => true, 'data' => $records]);
    }

    public function teamRecords(Request $request)
    {
        if (! $this->authz->isTenantAdmin($request->user())) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $date = $request->input('date', now()->toDateString());
        $records = AttendanceRecord::with('user')
            ->whereDate('work_date', $date)
            ->get();

        return response()->json(['status' => true, 'data' => $records]);
    }

    public function summary(Request $request, int $userId)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        return response()->json([
            'status' => true,
            'data' => $this->attendance->monthlySummary($userId, $year, $month),
        ]);
    }
}
