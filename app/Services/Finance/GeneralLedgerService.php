<?php

namespace App\Services\Finance;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GeneralLedgerService
{
    public function __construct(
        private readonly ChartOfAccountsService $coa,
    ) {}

    public function nextEntryNumber(int $tenantId): string
    {
        $prefix = config('finance.journal_prefix', 'JE');
        $seq = (int) JournalEntry::withoutGlobalScopes()->where('tenant_id', $tenantId)->max('id') + 1;

        return sprintf('%s-%s-%05d', $prefix, now()->format('Y'), $seq);
    }

    /**
     * @param  array<int, array{account_id?: int, account_role?: string, debit?: float, credit?: float, description?: string, folder_id?: int}>  $lines
     */
    public function post(
        int $tenantId,
        string $description,
        array $lines,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?int $createdBy = null,
        ?\DateTimeInterface $date = null,
    ): JournalEntry {
        return DB::transaction(function () use ($tenantId, $description, $lines, $sourceType, $sourceId, $createdBy, $date) {
            $entry = JournalEntry::create([
                'entry_number' => $this->nextEntryNumber($tenantId),
                'entry_date' => $date ?? now(),
                'description' => $description,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'status' => 'posted',
                'created_by' => $createdBy,
            ]);

            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                $accountId = $line['account_id']
                    ?? ($line['account_role'] ? $this->coa->accountIdByRole($tenantId, $line['account_role']) : null);

                if (! $accountId) {
                    throw new \InvalidArgumentException('Journal line missing account.');
                }

                $debit = round((float) ($line['debit'] ?? 0), 2);
                $credit = round((float) ($line['credit'] ?? 0), 2);
                $totalDebit += $debit;
                $totalCredit += $credit;

                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $accountId,
                    'debit' => $debit,
                    'credit' => $credit,
                    'description' => $line['description'] ?? null,
                    'folder_id' => $line['folder_id'] ?? null,
                    'currency' => $line['currency'] ?? config('finance.base_currency', 'GBP'),
                    'fx_rate' => $line['fx_rate'] ?? 1,
                ]);
            }

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                throw new \RuntimeException("Journal entry not balanced: DR {$totalDebit} != CR {$totalCredit}");
            }

            return $entry->load('lines.account');
        });
    }

    public function trialBalance(?string $from = null, ?string $to = null): Collection
    {
        $q = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.status', 'posted');

        if ($from) {
            $q->whereDate('journal_entries.entry_date', '>=', $from);
        }
        if ($to) {
            $q->whereDate('journal_entries.entry_date', '<=', $to);
        }

        return $q
            ->selectRaw('chart_of_accounts.code, chart_of_accounts.name, chart_of_accounts.type')
            ->selectRaw('SUM(journal_entry_lines.debit) as total_debit')
            ->selectRaw('SUM(journal_entry_lines.credit) as total_credit')
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_accounts.type')
            ->orderBy('chart_of_accounts.code')
            ->get()
            ->map(fn ($row) => [
                'code' => $row->code,
                'name' => $row->name,
                'type' => $row->type,
                'debit' => (float) $row->total_debit,
                'credit' => (float) $row->total_credit,
                'balance' => (float) $row->total_debit - (float) $row->total_credit,
            ]);
    }
}
