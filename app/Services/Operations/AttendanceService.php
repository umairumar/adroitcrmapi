<?php

namespace App\Services\Operations;

use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;

class AttendanceService
{
    public function clockIn(User $user, ?int $branchId = null): AttendanceRecord
    {
        $today = Carbon::today();

        $record = AttendanceRecord::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            [
                'tenant_id' => $user->tenant_id,
                'branch_id' => $branchId,
                'status' => 'present',
            ]
        );

        if (! $record->clock_in) {
            $record->update(['clock_in' => now()->format('H:i:s')]);
        }

        return $record->fresh();
    }

    public function clockOut(User $user): AttendanceRecord
    {
        $record = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('work_date', Carbon::today())
            ->firstOrFail();

        $clockOut = now()->format('H:i:s');
        $hours = null;

        if ($record->clock_in) {
            $in = Carbon::parse($record->work_date->format('Y-m-d') . ' ' . $record->clock_in);
            $out = Carbon::parse($record->work_date->format('Y-m-d') . ' ' . $clockOut);
            $hours = round($in->diffInMinutes($out) / 60, 2);
        }

        $record->update([
            'clock_out' => $clockOut,
            'hours_worked' => $hours,
        ]);

        return $record->fresh();
    }

    public function isOnApprovedLeave(User $user, Carbon $date): bool
    {
        return LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }

    public function monthlySummary(int $userId, int $year, int $month): array
    {
        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $records = AttendanceRecord::where('user_id', $userId)
            ->whereBetween('work_date', [$from, $to])
            ->get();

        return [
            'days_present' => $records->where('status', 'present')->count(),
            'total_hours' => round($records->sum('hours_worked'), 2),
            'absences' => $records->where('status', 'absent')->count(),
        ];
    }
}
