<?php

namespace App\Services\Operations;

use App\Models\BookingDeposit;
use App\Models\CrmFolders;
use App\Models\CrmPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DepositService
{
    public function syncFromLegacyInstallments(CrmFolders $folder): Collection
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('crm_folders_installments')) {
            return collect();
        }

        $rows = DB::table('crm_folders_installments')
            ->where('folder_id', $folder->id)
            ->get();

        $deposits = collect();

        foreach ($rows as $row) {
            $type = ((float) ($row->installment_due ?? 0)) < (float) ($row->installment_amount ?? 0)
                ? 'deposit'
                : 'installment';

            $paid = (int) ($row->installment_payment_status ?? 0) === 1
                ? (float) $row->installment_amount
                : 0;

            $deposit = BookingDeposit::firstOrCreate(
                [
                    'folder_id' => $folder->id,
                    'legacy_installment_id' => $row->id,
                ],
                [
                    'tenant_id' => $folder->tenant_id,
                    'label' => $type === 'deposit' ? 'Deposit' : 'Installment',
                    'deposit_type' => $type,
                    'amount' => (float) $row->installment_amount,
                    'paid_amount' => $paid,
                    'due_date' => $row->installment_payment_date,
                    'status' => $this->depositStatus((float) $row->installment_amount, $paid, $row->installment_payment_date),
                ]
            );

            $deposits->push($deposit);
        }

        $this->refreshFolderDepositTotals($folder);

        return $deposits;
    }

    public function createDepositSchedule(CrmFolders $folder, array $schedule): Collection
    {
        $created = collect();

        foreach ($schedule as $item) {
            $created->push(BookingDeposit::create([
                'tenant_id' => $folder->tenant_id,
                'folder_id' => $folder->id,
                'label' => $item['label'] ?? 'Payment',
                'deposit_type' => $item['deposit_type'] ?? 'installment',
                'amount' => (float) $item['amount'],
                'due_date' => $item['due_date'] ?? null,
                'paid_amount' => 0,
                'status' => 'pending',
            ]));
        }

        $this->refreshFolderDepositTotals($folder);

        return $created;
    }

    public function allocatePayment(CrmPayment $payment, ?int $depositId = null): void
    {
        $folder = CrmFolders::find($payment->folder_id);
        if (! $folder) {
            return;
        }

        $amount = (float) $payment->payment;

        if ($depositId) {
            $deposit = BookingDeposit::where('folder_id', $folder->id)->findOrFail($depositId);
            $this->applyPaymentToDeposit($deposit, $amount);
            $payment->update([
                'payment_type' => $deposit->deposit_type,
                'booking_deposit_id' => $deposit->id,
            ]);
        } else {
            $deposit = BookingDeposit::where('folder_id', $folder->id)
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->orderBy('due_date')
                ->first();

            if ($deposit) {
                $this->applyPaymentToDeposit($deposit, $amount);
                $payment->update([
                    'payment_type' => $deposit->deposit_type,
                    'booking_deposit_id' => $deposit->id,
                ]);
            }
        }

        $this->refreshFolderDepositTotals($folder);
        $this->refreshFolderRemaining($folder);
    }

    public function applyPaymentToDeposit(BookingDeposit $deposit, float $amount): void
    {
        $deposit->paid_amount = min(
            (float) $deposit->amount,
            (float) $deposit->paid_amount + $amount
        );
        $deposit->status = $this->depositStatus(
            (float) $deposit->amount,
            (float) $deposit->paid_amount,
            $deposit->due_date
        );
        $deposit->save();
    }

    private function depositStatus(float $amount, float $paid, $dueDate): string
    {
        if ($paid >= $amount) {
            return 'paid';
        }
        if ($paid > 0) {
            return 'partial';
        }
        if ($dueDate && Carbon::parse($dueDate)->isPast()) {
            return 'overdue';
        }

        return 'pending';
    }

    public function refreshFolderDepositTotals(CrmFolders $folder): void
    {
        $required = BookingDeposit::where('folder_id', $folder->id)
            ->where('deposit_type', 'deposit')
            ->sum('amount');

        $paid = BookingDeposit::where('folder_id', $folder->id)->sum('paid_amount');

        $folder->update([
            'deposit_required' => $required ?: null,
            'deposit_paid' => $paid,
        ]);
    }

    public function refreshFolderRemaining(CrmFolders $folder): void
    {
        $sell = (float) ($folder->sell ?? 0);
        $paid = CrmPayment::where('folder_id', $folder->id)
            ->where('status', 'approved')
            ->sum('payment');

        $folder->update(['remaining' => max(0, $sell - $paid)]);
    }

    public function markOverdueDeposits(): int
    {
        return BookingDeposit::whereIn('status', ['pending', 'partial'])
            ->whereDate('due_date', '<', Carbon::today())
            ->update(['status' => 'overdue']);
    }
}
