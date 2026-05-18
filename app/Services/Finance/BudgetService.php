<?php

namespace App\Services\Finance;

use App\Models\Budget;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

class BudgetService
{
    public function varianceReport(int $fiscalYear, ?int $month = null): Collection
    {
        $budgets = Budget::where('fiscal_year', $fiscalYear)
            ->when($month, fn ($q) => $q->where('period_month', $month))
            ->get();

        return $budgets->map(function (Budget $budget) use ($fiscalYear, $month) {
            $actualQ = JournalEntryLine::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                ->where('journal_entry_lines.account_id', $budget->account_id)
                ->whereYear('journal_entries.entry_date', $fiscalYear);

            if ($month) {
                $actualQ->whereMonth('journal_entries.entry_date', $month);
            }

            if ($budget->branch_id) {
                $actualQ->join('crm_folders', 'crm_folders.id', '=', 'journal_entry_lines.folder_id')
                    ->where('crm_folders.company', 'like', '%-' . $budget->branch_id . '-%');
            }

            $actual = (float) $actualQ->selectRaw('COALESCE(SUM(debit - credit), 0) as net')->value('net');

            return [
                'budget' => $budget,
                'budget_amount' => (float) $budget->amount,
                'actual' => $actual,
                'variance' => (float) $budget->amount - $actual,
            ];
        });
    }
}
