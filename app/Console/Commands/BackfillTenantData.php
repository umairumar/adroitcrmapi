<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Auth\BranchAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillTenantData extends Command
{
    protected $signature = 'saas:backfill-tenant
                            {--tenant= : Tenant slug to assign (default from config)}
                            {--dry-run : Preview changes without writing}';

    protected $description = 'Assign legacy rows to the default tenant and sync branch_user from user.company';

    public function handle(BranchAccess $branchAccess): int
    {
        $slug = $this->option('tenant') ?: config('saas.legacy_tenant_slug', 'default');
        $tenant = Tenant::where('slug', $slug)->first();

        if (! $tenant) {
            $this->error("Tenant [{$slug}] not found. Run: php artisan db:seed --class=SaasFoundationSeeder");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Backfilling tenant_id = {$tenant->id} ({$tenant->slug})");

        $tables = [
            'user', 'crm_company', 'crm_leads', 'crm_folders', 'crm_payments',
            'crm_leads_remarks', 'crm_hotels', 'crm_transport', 'crm_passengers',
            'crm_passengers_name', 'crm_others', 'crm_itinerary', 'crm_folders_installments',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                $this->warn("Skipping {$table} (missing table or tenant_id column)");

                continue;
            }

            $count = DB::table($table)->whereNull('tenant_id')->count();
            if ($count === 0) {
                $this->line("{$table}: nothing to backfill");

                continue;
            }

            if (! $dryRun) {
                DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $tenant->id]);
            }

            $this->info("{$table}: " . ($dryRun ? "would update" : 'updated') . " {$count} rows");
        }

        if (Schema::hasTable('user')) {
            $users = DB::table('user')->where('tenant_id', $tenant->id)->get();
            foreach ($users as $row) {
                $userModel = \App\Models\User::withoutGlobalScopes()->find($row->id);
                if (! $userModel || empty($userModel->company)) {
                    continue;
                }
                $branchIds = $branchAccess->legacyCompanyIdsFromUser($userModel);
                if ($branchIds === []) {
                    continue;
                }
                if ($dryRun) {
                    $this->line("user #{$row->id}: would sync branches [" . implode(',', $branchIds) . ']');
                } else {
                    $branchAccess->syncBranchesForUser($userModel, $branchIds);
                }
            }

            if (Schema::hasColumn('user', 'is_platform_admin')) {
                $sadminCount = DB::table('user')->where('utype', 'sadmin')->count();
                if ($sadminCount > 0 && ! $dryRun) {
                    DB::table('user')->where('utype', 'sadmin')->update(['is_platform_admin' => true]);
                    $this->info("Marked {$sadminCount} sadmin user(s) as platform admin");
                }
            }
        }

        $this->info('Backfill complete.');

        return self::SUCCESS;
    }
}
