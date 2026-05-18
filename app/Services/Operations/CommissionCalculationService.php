<?php

namespace App\Services\Operations;

use App\Models\CommissionEntry;
use App\Models\CrmFolders;
use App\Models\CrmHotel;
use App\Models\CrmOther;
use App\Models\CrmTransport;
use App\Models\StaffCommissionRule;
use App\Models\SupplierCommissionRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommissionCalculationService
{
    /**
     * Calculate and persist commission entries for a booking folder.
     *
     * @return Collection<int, CommissionEntry>
     */
    public function calculateForFolder(CrmFolders $folder, bool $replacePending = true): Collection
    {
        if ($replacePending) {
            CommissionEntry::where('folder_id', $folder->id)
                ->where('status', 'pending')
                ->delete();
        }

        $entries = collect();
        $entries = $entries->merge($this->calculateStaffCommissions($folder));
        $entries = $entries->merge($this->calculateSupplierCommissions($folder));

        return $entries;
    }

    private function calculateStaffCommissions(CrmFolders $folder): Collection
    {
        $rules = StaffCommissionRule::where('is_active', true)
            ->orderBy('id')
            ->get();

        $entries = collect();
        $sell = (float) ($folder->sell ?? 0);
        $cost = (float) ($folder->cost ?? 0);
        $profit = $sell - $cost;
        $folderCommission = (float) ($folder->commission ?? 0);

        foreach ($rules as $rule) {
            $userId = $this->resolveStaffUserId($folder, $rule);
            if (! $userId) {
                continue;
            }

            if ($rule->user_id && (int) $rule->user_id !== $userId) {
                continue;
            }

            $base = match ($rule->calculation_base) {
                'sell' => $sell,
                'cost' => $cost,
                'profit' => max(0, $profit),
                'folder_commission' => $folderCommission,
                default => $folderCommission,
            };

            $amount = $this->computeAmount($rule->calculation_type, (float) $rule->rate, $base);
            $amount = $this->applyMinMax($amount, $rule->min_amount, $rule->max_amount);

            if ($amount <= 0) {
                continue;
            }

            $entries->push(CommissionEntry::create([
                'tenant_id' => $folder->tenant_id,
                'folder_id' => $folder->id,
                'recipient_type' => 'staff',
                'user_id' => $userId,
                'rule_id' => $rule->id,
                'rule_type' => 'staff_commission_rule',
                'base_amount' => $base,
                'rate' => $rule->rate,
                'amount' => $amount,
                'status' => 'pending',
            ]));
        }

        return $entries;
    }

    private function resolveStaffUserId(CrmFolders $folder, StaffCommissionRule $rule): ?int
    {
        return match ($rule->applies_to) {
            'booked_by' => $folder->booked_by ? (int) $folder->booked_by : null,
            'agent' => $folder->cby ? (int) $folder->cby : null,
            default => $rule->user_id ? (int) $rule->user_id : ($folder->booked_by ? (int) $folder->booked_by : null),
        };
    }

    private function calculateSupplierCommissions(CrmFolders $folder): Collection
    {
        $rules = SupplierCommissionRule::where('is_active', true)->get();
        $entries = collect();

        $lines = $this->bookingComponentLines($folder);

        foreach ($lines as $line) {
            foreach ($rules as $rule) {
                if (! $this->supplierRuleMatches($rule, $line)) {
                    continue;
                }

                $base = match ($rule->calculation_base) {
                    'line_commission' => (float) ($line['commission'] ?? 0),
                    'sell' => (float) ($line['sell'] ?? 0),
                    'cost' => (float) ($line['cost'] ?? 0),
                    default => (float) ($line['commission'] ?? 0),
                };

                $amount = $this->computeAmount($rule->calculation_type, (float) $rule->rate, $base);
                if ($amount <= 0) {
                    continue;
                }

                $entries->push(CommissionEntry::create([
                    'tenant_id' => $folder->tenant_id,
                    'folder_id' => $folder->id,
                    'recipient_type' => 'supplier',
                    'supplier_id' => $rule->supplier_id,
                    'rule_id' => $rule->id,
                    'rule_type' => 'supplier_commission_rule',
                    'base_amount' => $base,
                    'rate' => $rule->rate,
                    'amount' => $amount,
                    'status' => 'pending',
                    'notes' => $line['supplier'] ?? null,
                ]));
            }
        }

        return $entries;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function bookingComponentLines(CrmFolders $folder): array
    {
        $lines = [];

        foreach (CrmHotel::where('folder_id', $folder->id)->get() as $row) {
            $lines[] = [
                'component' => 'hotel',
                'supplier' => $row->supplier,
                'cost' => $row->cost,
                'sell' => $row->sell,
                'commission' => $row->commission,
            ];
        }

        foreach (CrmTransport::where('folder_id', $folder->id)->get() as $row) {
            $lines[] = [
                'component' => 'transport',
                'supplier' => $row->supplier ?? null,
                'cost' => $row->cost,
                'sell' => $row->sell,
                'commission' => $row->commission,
            ];
        }

        foreach (CrmOther::where('folder_id', $folder->id)->get() as $row) {
            $lines[] = [
                'component' => 'other',
                'supplier' => $row->supplier ?? null,
                'cost' => $row->cost,
                'sell' => $row->sell,
                'commission' => $row->commission,
            ];
        }

        return $lines;
    }

    private function supplierRuleMatches(SupplierCommissionRule $rule, array $line): bool
    {
        if ($rule->component !== 'any' && $rule->component !== ($line['component'] ?? '')) {
            return false;
        }

        if ($rule->supplier_name_match) {
            $supplier = strtolower((string) ($line['supplier'] ?? ''));

            return str_contains($supplier, strtolower($rule->supplier_name_match));
        }

        return true;
    }

    private function computeAmount(string $type, float $rate, float $base): float
    {
        if ($type === 'fixed') {
            return round($rate, 2);
        }

        return round($base * ($rate / 100), 2);
    }

    private function applyMinMax(float $amount, $min, $max): float
    {
        if ($min !== null && $amount < (float) $min) {
            $amount = (float) $min;
        }
        if ($max !== null && $amount > (float) $max) {
            $amount = (float) $max;
        }

        return $amount;
    }

    public function createPayout(
        string $recipientType,
        $periodStart,
        $periodEnd,
        ?int $userId = null,
        ?int $supplierId = null,
        ?int $createdBy = null,
    ): \App\Models\CommissionPayout {
        return DB::transaction(function () use ($recipientType, $periodStart, $periodEnd, $userId, $supplierId, $createdBy) {
            $query = CommissionEntry::where('status', 'approved')
                ->whereNull('payout_id')
                ->whereBetween('created_at', [$periodStart, $periodEnd]);

            if ($recipientType === 'staff' && $userId) {
                $query->where('user_id', $userId)->where('recipient_type', 'staff');
            }

            if ($recipientType === 'supplier' && $supplierId) {
                $query->where('supplier_id', $supplierId)->where('recipient_type', 'supplier');
            }

            $entries = $query->get();
            $total = $entries->sum('amount');

            $payout = \App\Models\CommissionPayout::create([
                'recipient_type' => $recipientType,
                'user_id' => $userId,
                'supplier_id' => $supplierId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'total_amount' => $total,
                'status' => 'draft',
                'created_by' => $createdBy,
            ]);

            CommissionEntry::whereIn('id', $entries->pluck('id'))
                ->update(['payout_id' => $payout->id]);

            return $payout->load('entries');
        });
    }

    public function reportSummary(?int $tenantId, ?string $from, ?string $to): array
    {
        $q = CommissionEntry::query();
        if ($from) {
            $q->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $q->whereDate('created_at', '<=', $to);
        }

        return [
            'pending' => (float) (clone $q)->where('status', 'pending')->sum('amount'),
            'approved' => (float) (clone $q)->where('status', 'approved')->sum('amount'),
            'paid' => (float) (clone $q)->where('status', 'paid')->sum('amount'),
            'by_staff' => (clone $q)->where('recipient_type', 'staff')
                ->selectRaw('user_id, SUM(amount) as total')
                ->groupBy('user_id')
                ->pluck('total', 'user_id'),
        ];
    }
}
