<?php

namespace App\Services\Finance;

use App\Models\CrmFolders;
use App\Models\CrmPayment;
use App\Models\CustomerInvoice;

class FinanceIntegrationService
{
    public function __construct(
        private readonly AccountsReceivableService $ar,
    ) {}

    public function onPaymentApproved(CrmPayment $payment): void
    {
        $folder = CrmFolders::find($payment->folder_id);
        if (! $folder) {
            return;
        }

        $invoice = CustomerInvoice::where('folder_id', $folder->id)
            ->whereIn('status', ['sent', 'partial'])
            ->orderByDesc('id')
            ->first();

        if (! $invoice) {
            return;
        }

        $this->ar->allocatePayment($invoice, (float) $payment->payment, $payment);
    }
}
