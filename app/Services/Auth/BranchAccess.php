<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class BranchAccess
{
    /**
     * Branch IDs the user may access within their tenant.
     *
     * @return int[]
     */
    public function branchIdsFor(User $user): array
    {
        if ($user->isPlatformAdmin()) {
            return [];
        }

        $fromPivot = DB::table('branch_user')
            ->where('user_id', $user->id)
            ->when($user->tenant_id, fn ($q) => $q->where('tenant_id', $user->tenant_id))
            ->pluck('branch_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($fromPivot !== []) {
            return $fromPivot;
        }

        return $this->legacyCompanyIdsFromUser($user);
    }

    /**
     * Legacy dash-delimited company field: "-2-4-" → [2, 4]
     *
     * @return int[]
     */
    public function legacyCompanyIdsFromUser(User $user): array
    {
        if (empty($user->company)) {
            return [];
        }

        return array_values(array_filter(
            array_map('intval', explode('-', trim((string) $user->company, '-'))),
            fn ($id) => $id > 0
        ));
    }

    public function userCanAccessBranch(User $user, int $branchId): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        $ids = $this->branchIdsFor($user);

        return in_array($branchId, $ids, true);
    }

    /**
     * Apply branch filter on a query column that stores dash-wrapped IDs (legacy).
     */
    public function scopeLegacyCompanyColumn($query, string $column, User $user): void
    {
        $branchIds = $this->branchIdsFor($user);

        if ($branchIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function ($inner) use ($column, $branchIds) {
            foreach ($branchIds as $branchId) {
                $inner->orWhere($column, 'like', "%-{$branchId}-%");
            }
        });
    }

    public function syncBranchesForUser(User $user, array $branchIds): void
    {
        if (! $user->tenant_id) {
            return;
        }

        DB::table('branch_user')->where('user_id', $user->id)->delete();

        $rows = [];
        foreach (array_unique(array_map('intval', $branchIds)) as $branchId) {
            if ($branchId <= 0) {
                continue;
            }
            $rows[] = [
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows !== []) {
            DB::table('branch_user')->insert($rows);
        }

        $user->company = '-' . implode('-', array_column($rows, 'branch_id')) . '-';
        $user->saveQuietly();
    }
}
