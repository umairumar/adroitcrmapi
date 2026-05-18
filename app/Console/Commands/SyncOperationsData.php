<?php

namespace App\Console\Commands;

use App\Models\CrmFolders;
use App\Services\Operations\DepositService;
use Illuminate\Console\Command;

class SyncOperationsData extends Command
{
    protected $signature = 'operations:sync-deposits {--folder=}';

    protected $description = 'Sync booking deposits from legacy crm_folders_installments';

    public function handle(DepositService $deposits): int
    {
        $query = CrmFolders::withoutGlobalScopes();
        if ($this->option('folder')) {
            $query->where('id', $this->option('folder'));
        }

        $count = 0;
        $query->chunkById(50, function ($folders) use ($deposits, &$count) {
            foreach ($folders as $folder) {
                $deposits->syncFromLegacyInstallments($folder);
                $count++;
            }
        });

        $overdue = $deposits->markOverdueDeposits();
        $this->info("Synced {$count} folder(s). Marked {$overdue} deposit(s) overdue.");

        return self::SUCCESS;
    }
}
