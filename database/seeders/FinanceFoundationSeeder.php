<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Services\Finance\ChartOfAccountsService;
use Illuminate\Database\Seeder;

class FinanceFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $coa = app(ChartOfAccountsService::class);

        Tenant::query()->each(fn (Tenant $t) => $coa->seedForTenant($t->id));
    }
}
