<?php

namespace App\Console\Commands;

use App\Services\Engagement\CampaignService;
use Illuminate\Console\Command;

class ProcessEngagementCampaigns extends Command
{
    protected $signature = 'engagement:process-campaigns';

    protected $description = 'Send due campaign drip messages to recipients';

    public function handle(CampaignService $campaigns): int
    {
        $sent = $campaigns->processDueSends();
        $this->info("Processed {$sent} campaign recipient(s).");

        return self::SUCCESS;
    }
}
