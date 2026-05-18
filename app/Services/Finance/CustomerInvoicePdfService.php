<?php

namespace App\Services\Finance;

use App\Models\BankAccount;
use App\Models\CustomerInvoice;
use App\Models\CrmFolders;
use App\Models\Tenant;
use App\Models\TenantBranding;
use App\Services\Integrations\WhiteLabelService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class CustomerInvoicePdfService
{
    public function __construct(
        private readonly WhiteLabelService $whiteLabel,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildViewData(CustomerInvoice $invoice): array
    {
        $invoice->loadMissing(['lines', 'folder.passengersNames', 'folder.hotels', 'folder.lead', 'folder.payments']);

        $folder = $invoice->folder;
        $tenant = Tenant::find($invoice->tenant_id);
        $branding = $this->whiteLabel->forTenant($invoice->tenant_id);

        $billTo = $this->resolveBillToName($folder);
        $payments = $folder ? $folder->payments : collect();
        $amountPaid = (float) $invoice->amount_paid;
        if ($amountPaid <= 0 && $payments->isNotEmpty()) {
            $amountPaid = (float) $payments
                ->where('status', 'approved')
                ->sum('payment');
        }

        $bank = $this->resolveBankDetails($invoice->tenant_id, $branding);

        return [
            'invoice' => $invoice,
            'folder' => $folder,
            'tenant' => $tenant,
            'branding' => $branding,
            'billTo' => $billTo,
            'bookingRef' => $folder ? (string) $folder->id : null,
            'vendorRef' => $folder?->vendor_ref,
            'destination' => $folder?->destination,
            'travelDate' => $folder?->travel_date,
            'passengerCount' => $folder?->no_of_passengers,
            'ziaraatsMakkah' => $folder?->ziaraats_makkah,
            'ziaraatsMadinah' => $folder?->ziaraats_madinah,
            'balanceDueDate' => $folder?->balanceduedate,
            'passengers' => $folder?->passengersNames ?? collect(),
            'amountPaid' => $amountPaid,
            'balanceDue' => max(0, (float) $invoice->total - $amountPaid),
            'depositPaid' => (float) ($folder?->deposit_paid ?? 0),
            'bank' => $bank,
            'title' => config('invoices.title', 'INVOICE'),
            'terms' => $branding->invoice_terms ?: config('invoices.default_terms'),
            'paymentInstructions' => $branding->invoice_payment_instructions,
        ];
    }

    public function download(CustomerInvoice $invoice): Response
    {
        $data = $this->buildViewData($invoice);
        $filename = $this->filename($invoice, $data['billTo']);

        return Pdf::loadView('invoices.customer', $data)
            ->setPaper('a4')
            ->download($filename);
    }

    public function stream(CustomerInvoice $invoice): Response
    {
        $data = $this->buildViewData($invoice);

        return Pdf::loadView('invoices.customer', $data)
            ->setPaper('a4')
            ->stream($this->filename($invoice, $data['billTo']));
    }

    public function html(CustomerInvoice $invoice): string
    {
        $data = $this->buildViewData($invoice);

        return view('invoices.customer', $data)->render();
    }

    private function filename(CustomerInvoice $invoice, string $billTo): string
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $billTo) ?: 'customer';

        return sprintf('Invoice_%s_%s.pdf', $safeName, $invoice->invoice_number);
    }

    private function resolveBillToName(?CrmFolders $folder): string
    {
        if (! $folder) {
            return 'Customer';
        }

        $primary = $folder->passengersNames->first();
        if ($primary) {
            $parts = array_filter([
                $primary->title,
                $primary->fname,
                $primary->mname,
                $primary->lname,
            ]);

            return trim(implode(' ', $parts)) ?: 'Customer';
        }

        if ($folder->lead?->name) {
            return $folder->lead->name;
        }

        if ($folder->booked_by) {
            return $folder->booked_by;
        }

        return 'Customer';
    }

    /**
     * @return array<string, string|null>
     */
    private function resolveBankDetails(int $tenantId, TenantBranding $branding): array
    {
        if ($branding->invoice_bank_name || $branding->invoice_account_number) {
            return [
                'bank_name' => $branding->invoice_bank_name,
                'sort_code' => $branding->invoice_sort_code,
                'account_number' => $branding->invoice_account_number,
                'iban' => $branding->invoice_iban,
            ];
        }

        $account = BankAccount::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (! $account) {
            return [
                'bank_name' => null,
                'sort_code' => null,
                'account_number' => null,
                'iban' => null,
            ];
        }

        return [
            'bank_name' => $account->bank_name ?: $account->name,
            'sort_code' => null,
            'account_number' => $account->account_number,
            'iban' => null,
        ];
    }
}
