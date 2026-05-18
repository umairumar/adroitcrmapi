<?php

namespace App\Services\Finance;

use App\Models\CrmFolders;
use App\Models\CustomerInvoice;
use Carbon\Carbon;

class RevenueRecognitionService
{
    public function recognize(CustomerInvoice $invoice): void
    {
        if ($invoice->recognized_at) {
            return;
        }

        $invoice->update(['recognized_at' => Carbon::today()]);
    }

    public function processTravelDateRecognition(): int
    {
        $count = 0;
        $today = Carbon::today();

        CustomerInvoice::where('revenue_recognition', 'on_travel_date')
            ->whereNull('recognized_at')
            ->whereIn('status', ['sent', 'partial', 'paid'])
            ->chunkById(50, function ($invoices) use ($today, &$count) {
                foreach ($invoices as $invoice) {
                    $folder = CrmFolders::find($invoice->folder_id);
                    if (! $folder?->travel_date) {
                        continue;
                    }
                    if (Carbon::parse($folder->travel_date)->lte($today)) {
                        $this->recognize($invoice);
                        $count++;
                    }
                }
            });

        return $count;
    }
}
