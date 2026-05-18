<?php

namespace App\Console\Commands;

use App\Services\Integrations\WebhookDispatcher;
use Illuminate\Console\Command;

class DeliverWebhooks extends Command
{
    protected $signature = 'webhooks:deliver';

    protected $description = 'Retry pending outbound webhook deliveries';

    public function handle(WebhookDispatcher $dispatcher): int
    {
        $count = $dispatcher->processRetries();
        $this->info("Processed {$count} webhook delivery attempt(s).");

        return self::SUCCESS;
    }
}
