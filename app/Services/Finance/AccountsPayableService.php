<?php

namespace App\Services\Finance;

use App\Models\SupplierBill;
use App\Models\SupplierBillLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccountsPayableService
{
    public function __construct(
        private readonly GeneralLedgerService $gl,
        private readonly TaxService $tax,
    ) {}

    public function nextBillNumber(int $tenantId): string
    {
        $prefix = config('finance.bill_prefix', 'BILL');
        $seq = SupplierBill::withoutGlobalScopes()->where('tenant_id', $tenantId)->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, now()->format('Y'), $seq);
    }

    public function createBill(array $data, ?int $createdBy = null): SupplierBill
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($data['lines'] ?? [] as $line) {
                $amt = (float) ($line['amount'] ?? 0);
                $tax = $this->tax->calculate($amt, $line['tax_rate_id'] ?? null);
                $subtotal += $amt;
                $taxTotal += $tax['tax_amount'];
            }

            $total = round($subtotal + $taxTotal, 2);

            $tenantId = $data['tenant_id'] ?? \App\Services\Tenant\TenantContext::id();

            $bill = SupplierBill::create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'folder_id' => $data['folder_id'] ?? null,
                'bill_number' => $this->nextBillNumber((int) $tenantId),
                'supplier_reference' => $data['supplier_reference'] ?? null,
                'issue_date' => $data['issue_date'] ?? Carbon::today(),
                'due_date' => $data['due_date'] ?? Carbon::today()->addDays(30),
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'total' => $total,
                'status' => 'approved',
            ]);

            foreach ($data['lines'] ?? [] as $line) {
                $amt = (float) ($line['amount'] ?? 0);
                $tax = $this->tax->calculate($amt, $line['tax_rate_id'] ?? null);
                SupplierBillLine::create([
                    'supplier_bill_id' => $bill->id,
                    'description' => $line['description'],
                    'amount' => $amt,
                    'tax_rate_id' => $tax['tax_rate_id'] ?? null,
                    'tax_amount' => $tax['tax_amount'],
                ]);
            }

            $entry = $this->gl->post($bill->tenant_id, 'Supplier bill ' . $bill->bill_number, [
                ['account_role' => 'cogs', 'debit' => $subtotal, 'folder_id' => $bill->folder_id],
                ['account_role' => 'tax_payable', 'debit' => $taxTotal, 'folder_id' => $bill->folder_id],
                ['account_role' => 'ap', 'credit' => $total, 'folder_id' => $bill->folder_id],
            ], 'supplier_bill', $bill->id, $createdBy, $bill->issue_date);

            $bill->update(['journal_entry_id' => $entry->id]);

            return $bill->fresh('lines');
        });
    }

    public function recordPayment(SupplierBill $bill, float $amount, ?string $reference = null): void
    {
        DB::transaction(function () use ($bill, $amount, $reference) {
            $bill->amount_paid = (float) $bill->amount_paid + $amount;
            $bill->status = $bill->amount_paid >= $bill->total ? 'paid' : 'partial';
            $bill->save();

            DB::table('ap_payment_allocations')->insert([
                'tenant_id' => $bill->tenant_id,
                'supplier_bill_id' => $bill->id,
                'amount' => $amount,
                'payment_date' => now()->toDateString(),
                'payment_reference' => $reference,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->gl->post($bill->tenant_id, 'Supplier payment ' . $bill->bill_number, [
                ['account_role' => 'ap', 'debit' => $amount, 'folder_id' => $bill->folder_id],
                ['account_role' => 'cash', 'credit' => $amount, 'folder_id' => $bill->folder_id],
            ], 'ap_payment', $bill->id, null, now());
        });
    }

    public function agingReport(): Collection
    {
        return SupplierBill::whereIn('status', ['approved', 'partial'])
            ->get()
            ->map(fn ($b) => [
                'bill' => $b,
                'balance' => max(0, (float) $b->total - (float) $b->amount_paid),
                'days_overdue' => Carbon::parse($b->due_date)->diffInDays(Carbon::today(), false),
            ]);
    }
}
