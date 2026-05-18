<?php

namespace App\Services\Finance;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\CrmPayment;
use Carbon\Carbon;

class BankReconciliationService
{
    /**
     * Import CSV rows: date, description, amount (signed or debit/credit columns).
     *
     * @param  array<int, array<string, string>>  $rows
     */
    public function importCsv(BankAccount $account, array $rows): int
    {
        $imported = 0;

        foreach ($rows as $row) {
            $amount = (float) ($row['amount'] ?? $row['credit'] ?? 0) - (float) ($row['debit'] ?? 0);
            if ($amount == 0) {
                continue;
            }

            BankTransaction::create([
                'tenant_id' => $account->tenant_id,
                'bank_account_id' => $account->id,
                'transaction_date' => Carbon::parse($row['date'] ?? now()),
                'description' => $row['description'] ?? 'Import',
                'amount' => abs($amount),
                'type' => $amount >= 0 ? 'credit' : 'debit',
                'reference' => $row['reference'] ?? null,
            ]);

            $imported++;
        }

        return $imported;
    }

    public function suggestMatches(BankAccount $account): array
    {
        $unreconciled = BankTransaction::where('bank_account_id', $account->id)
            ->where('is_reconciled', false)
            ->get();

        $suggestions = [];

        foreach ($unreconciled as $txn) {
            if ($txn->type !== 'credit') {
                continue;
            }

            $payment = CrmPayment::where('status', 'approved')
                ->where('payment', $txn->amount)
                ->whereDate('pdate', $txn->transaction_date)
                ->first();

            if ($payment) {
                $suggestions[] = [
                    'bank_transaction_id' => $txn->id,
                    'crm_payment_id' => $payment->id,
                    'confidence' => 'high',
                ];
            }
        }

        return $suggestions;
    }

    public function reconcile(BankTransaction $txn, ?int $paymentId = null): void
    {
        $txn->update([
            'is_reconciled' => true,
            'matched_payment_id' => $paymentId,
        ]);
    }
}
