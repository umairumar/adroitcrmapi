<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Billing\TenantBillingService;
use Illuminate\Console\Command;

class SyncTenantBillingStatus extends Command
{
    protected $signature = 'saas:sync-billing {--mark-overdue : Mark sent invoices past due as overdue}';

    protected $description = 'Sync tenant billing_status from invoice state (for cron/scheduler)';

    public function handle(TenantBillingService $billing): int
    {
        if ($this->option('mark-overdue')) {
            $count = $billing->markOverdueInvoices();
            $this->info("Marked {$count} invoice(s) as overdue.");
        }

        $synced = 0;
        Tenant::chunkById(50, function ($tenants) use ($billing, &$synced) {
            foreach ($tenants as $tenant) {
                $billing->syncTenantBillingStatus($tenant);
                $synced++;
            }
        });

        $this->info("Synced billing status for {$synced} tenant(s).");

        return self::SUCCESS;
    }
}
