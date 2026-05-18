<?php

namespace App\Services\Finance;

use App\Models\CrmFolders;
use App\Models\CrmPayment;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccountsReceivableService
{
    public function __construct(
        private readonly GeneralLedgerService $gl,
        private readonly TaxService $tax,
        private readonly ChartOfAccountsService $coa,
        private readonly RevenueRecognitionService $revenue,
    ) {}

    public function nextInvoiceNumber(int $tenantId): string
    {
        $prefix = config('finance.invoice_prefix', 'INV');
        $seq = CustomerInvoice::withoutGlobalScopes()->where('tenant_id', $tenantId)->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, now()->format('Y'), $seq);
    }

    public function createFromFolder(CrmFolders $folder, ?int $createdBy = null): CustomerInvoice
    {
        return DB::transaction(function () use ($folder, $createdBy) {
            $folder->loadMissing(['passengersNames', 'hotels', 'lead', 'payments']);

            $sell = (float) ($folder->sell ?? 0);
            $taxCalc = $this->tax->calculate($sell);
            $terms = 30;
            $contactId = $folder->lead?->contact_id;

            $invoice = CustomerInvoice::create([
                'folder_id' => $folder->id,
                'contact_id' => $contactId,
                'invoice_number' => $this->nextInvoiceNumber($folder->tenant_id),
                'issue_date' => Carbon::today(),
                'due_date' => Carbon::today()->addDays($terms),
                'currency' => config('finance.base_currency', 'GBP'),
                'subtotal' => $sell,
                'tax_amount' => $taxCalc['tax_amount'],
                'total' => $taxCalc['gross'],
                'status' => 'sent',
                'revenue_recognition' => 'on_payment',
                'notes' => $this->buildInvoiceNotes($folder),
            ]);

            foreach ($this->buildLineItems($folder, $sell, $taxCalc) as $line) {
                CustomerInvoiceLine::create(array_merge(
                    ['customer_invoice_id' => $invoice->id],
                    $line
                ));
            }

            $folder->update(['invoice_status' => 'invoiced']);

            $this->postInvoiceToGl($invoice, $folder->tenant_id, $createdBy);

            return $invoice->fresh(['lines', 'folder.passengersNames']);
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildLineItems(CrmFolders $folder, float $sell, array $taxCalc): array
    {
        $destination = $folder->destination ?: 'Travel package';
        $pax = $folder->no_of_passengers ?: $folder->passengersNames->count() ?: 1;
        $mainDescription = sprintf(
            '%s — %s passenger(s)%s',
            $destination,
            $pax,
            $folder->travel_date ? ' — departure ' . $folder->travel_date : ''
        );

        $lines = [[
            'description' => $mainDescription,
            'quantity' => 1,
            'unit_price' => $sell,
            'tax_rate_id' => $taxCalc['tax_rate_id'] ?? null,
            'tax_amount' => $taxCalc['tax_amount'],
            'line_total' => $taxCalc['gross'],
        ]];

        foreach ($folder->hotels as $hotel) {
            if (! $hotel->hotel_name) {
                continue;
            }
            $lines[] = [
                'description' => sprintf(
                    'Hotel: %s, %s (%s nights)',
                    $hotel->hotel_name,
                    $hotel->city ?: '—',
                    $hotel->nights ?: '—'
                ),
                'quantity' => 1,
                'unit_price' => 0,
                'tax_rate_id' => null,
                'tax_amount' => 0,
                'line_total' => 0,
            ];
        }

        return $lines;
    }

    private function buildInvoiceNotes(CrmFolders $folder): ?string
    {
        $parts = array_filter([
            $folder->ziaraats_makkah ? 'Makkah ziaraat: ' . $folder->ziaraats_makkah : null,
            $folder->ziaraats_madinah ? 'Madinah ziaraat: ' . $folder->ziaraats_madinah : null,
            $folder->balanceduedate ? 'Balance due by: ' . $folder->balanceduedate : null,
        ]);

        return $parts ? implode('. ', $parts) : null;
    }

    public function postInvoiceToGl(CustomerInvoice $invoice, int $tenantId, ?int $createdBy): void
    {
        $entry = $this->gl->post($tenantId, 'Customer invoice ' . $invoice->invoice_number, [
            ['account_role' => 'ar', 'debit' => (float) $invoice->total, 'folder_id' => $invoice->folder_id],
            ['account_role' => 'revenue', 'credit' => (float) $invoice->subtotal, 'folder_id' => $invoice->folder_id],
            ['account_role' => 'tax_payable', 'credit' => (float) $invoice->tax_amount, 'folder_id' => $invoice->folder_id],
        ], 'customer_invoice', $invoice->id, $createdBy, $invoice->issue_date);

        $invoice->update(['journal_entry_id' => $entry->id]);

        if ($invoice->revenue_recognition === 'on_invoice') {
            $this->revenue->recognize($invoice);
        }
    }

    public function allocatePayment(CustomerInvoice $invoice, float $amount, ?CrmPayment $payment = null): void
    {
        DB::transaction(function () use ($invoice, $amount, $payment) {
            $invoice->amount_paid = (float) $invoice->amount_paid + $amount;
            $invoice->status = $invoice->amount_paid >= $invoice->total ? 'paid' : 'partial';
            $invoice->save();

            DB::table('ar_payment_allocations')->insert([
                'tenant_id' => $invoice->tenant_id,
                'customer_invoice_id' => $invoice->id,
                'crm_payment_id' => $payment?->id,
                'amount' => $amount,
                'allocated_date' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->gl->post($invoice->tenant_id, 'Payment received ' . $invoice->invoice_number, [
                ['account_role' => 'cash', 'debit' => $amount, 'folder_id' => $invoice->folder_id],
                ['account_role' => 'ar', 'credit' => $amount, 'folder_id' => $invoice->folder_id],
            ], 'ar_payment', $invoice->id, null, now());

            if ($invoice->revenue_recognition === 'on_payment' && $invoice->status === 'paid') {
                $this->revenue->recognize($invoice->fresh());
            }
        });
    }

    public function agingReport(): Collection
    {
        $today = Carbon::today();

        return CustomerInvoice::whereIn('status', ['sent', 'partial', 'overdue'])
            ->get()
            ->groupBy(function (CustomerInvoice $inv) use ($today) {
                $due = Carbon::parse($inv->due_date);
                $days = $due->diffInDays($today, false);

                if ($days < 0) {
                    return 'current';
                }
                if ($days <= 30) {
                    return '1_30';
                }
                if ($days <= 60) {
                    return '31_60';
                }
                if ($days <= 90) {
                    return '61_90';
                }

                return '90_plus';
            })
            ->map(fn ($group, $bucket) => [
                'bucket' => $bucket,
                'count' => $group->count(),
                'total' => round($group->sum(fn ($i) => $i->balanceDue()), 2),
                'invoices' => $group,
            ]);
    }
}
