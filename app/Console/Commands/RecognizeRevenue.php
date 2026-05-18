<?php

namespace App\Console\Commands;

use App\Services\Finance\RevenueRecognitionService;
use Illuminate\Console\Command;

class RecognizeRevenue extends Command
{
    protected $signature = 'finance:recognize-revenue';

    protected $description = 'Recognize revenue for invoices configured on travel date';

    public function handle(RevenueRecognitionService $service): int
    {
        $count = $service->processTravelDateRecognition();
        $this->info("Recognized revenue for {$count} invoice(s).");

        return self::SUCCESS;
    }
}
