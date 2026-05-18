<?php

namespace App\Services\Sales;

use App\Models\CrmLead;
use App\Models\LeadAssignmentRule;
use Carbon\Carbon;

class LeadAssignmentService
{
    public function autoAssign(CrmLead $lead): ?int
    {
        if ($lead->agent && (int) $lead->agent > 0) {
            return (int) $lead->agent;
        }

        if (! $lead->tenant_id) {
            return null;
        }

        $rules = LeadAssignmentRule::where('is_active', true)
            ->orderBy('priority')
            ->get();

        foreach ($rules as $rule) {
            if ($this->matches($lead, $rule->conditions ?? [])) {
                $lead->update([
                    'agent' => $rule->assign_to_user_id,
                    'assigned_at' => Carbon::now(),
                    'mdate' => now(),
                ]);

                return (int) $rule->assign_to_user_id;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    private function matches(CrmLead $lead, array $conditions): bool
    {
        if (empty($conditions)) {
            return false;
        }

        if (! empty($conditions['source']) && $lead->source !== $conditions['source']) {
            return false;
        }

        if (! empty($conditions['lead_type']) && $lead->lead_type !== $conditions['lead_type']) {
            return false;
        }

        if (! empty($conditions['destination']) && stripos((string) $lead->destination, $conditions['destination']) === false) {
            return false;
        }

        if (! empty($conditions['company_contains'])) {
            $needle = $conditions['company_contains'];
            if (! str_contains((string) $lead->company, (string) $needle)) {
                return false;
            }
        }

        return true;
    }
}
