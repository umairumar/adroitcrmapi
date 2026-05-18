<?php

namespace App\Services\Finance;

use App\Models\ChartOfAccount;
use App\Models\TaxRate;

class ChartOfAccountsService
{
    public function seedForTenant(int $tenantId): void
    {
        foreach (config('finance.default_accounts', []) as $account) {
            ChartOfAccount::withoutGlobalScopes()->firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => $account['code']],
                [
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
        }

        TaxRate::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'VAT20'],
            ['name' => 'VAT 20%', 'rate' => 20, 'is_default' => true, 'is_active' => true]
        );
    }

    public function accountIdByRole(int $tenantId, string $role): ?int
    {
        $code = config('finance.account_roles.' . $role);
        if (! $code) {
            return null;
        }

        return ChartOfAccount::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->value('id');
    }
}
