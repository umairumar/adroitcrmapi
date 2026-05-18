<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Services\Finance\ChartOfAccountsService;
use App\Services\Sales\PipelineService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaasFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'View leads', 'slug' => 'leads.view', 'module' => 'leads'],
            ['name' => 'Manage leads', 'slug' => 'leads.manage', 'module' => 'leads'],
            ['name' => 'Assign leads', 'slug' => 'leads.assign', 'module' => 'leads'],
            ['name' => 'View companies/branches', 'slug' => 'companies.view', 'module' => 'companies'],
            ['name' => 'Manage companies/branches', 'slug' => 'companies.manage', 'module' => 'companies'],
            ['name' => 'View users', 'slug' => 'users.view', 'module' => 'users'],
            ['name' => 'Manage users', 'slug' => 'users.manage', 'module' => 'users'],
            ['name' => 'View bookings/folders', 'slug' => 'folders.view', 'module' => 'folders'],
            ['name' => 'Manage bookings/folders', 'slug' => 'folders.manage', 'module' => 'folders'],
            ['name' => 'View payments', 'slug' => 'payments.view', 'module' => 'payments'],
            ['name' => 'Process payments', 'slug' => 'payments.process', 'module' => 'payments'],
            ['name' => 'View dashboard', 'slug' => 'dashboard.view', 'module' => 'dashboard'],
            ['name' => 'Manage tenant settings', 'slug' => 'tenant.settings', 'module' => 'tenant'],
            ['name' => 'Platform administration', 'slug' => 'platform.admin', 'module' => 'platform'],
            ['name' => 'View sales pipeline', 'slug' => 'pipeline.view', 'module' => 'pipeline'],
            ['name' => 'Manage sales pipeline', 'slug' => 'pipeline.manage', 'module' => 'pipeline'],
            ['name' => 'View contacts', 'slug' => 'contacts.view', 'module' => 'contacts'],
            ['name' => 'Manage contacts', 'slug' => 'contacts.manage', 'module' => 'contacts'],
            ['name' => 'View commissions', 'slug' => 'commissions.view', 'module' => 'operations'],
            ['name' => 'Manage commissions', 'slug' => 'commissions.manage', 'module' => 'operations'],
            ['name' => 'Manage deposits', 'slug' => 'deposits.manage', 'module' => 'operations'],
            ['name' => 'Manage suppliers', 'slug' => 'suppliers.manage', 'module' => 'operations'],
            ['name' => 'Manage receipts', 'slug' => 'receipts.manage', 'module' => 'operations'],
            ['name' => 'View attendance', 'slug' => 'attendance.view', 'module' => 'operations'],
            ['name' => 'Manage attendance', 'slug' => 'attendance.manage', 'module' => 'operations'],
            ['name' => 'View finance', 'slug' => 'finance.view', 'module' => 'finance'],
            ['name' => 'Manage finance', 'slug' => 'finance.manage', 'module' => 'finance'],
            ['name' => 'View inbox', 'slug' => 'inbox.view', 'module' => 'engagement'],
            ['name' => 'Manage inbox', 'slug' => 'inbox.manage', 'module' => 'engagement'],
            ['name' => 'Manage campaigns', 'slug' => 'campaigns.manage', 'module' => 'engagement'],
            ['name' => 'View analytics', 'slug' => 'analytics.view', 'module' => 'engagement'],
            ['name' => 'Manage B2C portal', 'slug' => 'portal.manage', 'module' => 'engagement'],
            ['name' => 'View loyalty', 'slug' => 'loyalty.view', 'module' => 'engagement'],
            ['name' => 'Manage loyalty', 'slug' => 'loyalty.manage', 'module' => 'engagement'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['slug' => $perm['slug']], $perm);
        }

        $allPermissionIds = Permission::pluck('id', 'slug');

        $roleDefinitions = [
            'platform_admin' => [
                'name' => 'Platform Administrator',
                'permissions' => ['*'],
            ],
            'tenant_admin' => [
                'name' => 'Tenant Administrator',
                'permissions' => [
                    'leads.view', 'leads.manage', 'leads.assign',
                    'companies.view', 'companies.manage',
                    'users.view', 'users.manage',
                    'folders.view', 'folders.manage',
                    'payments.view', 'payments.process',
                    'dashboard.view', 'tenant.settings',
                    'pipeline.view', 'pipeline.manage',
                    'contacts.view', 'contacts.manage',
                    'commissions.view', 'commissions.manage',
                    'deposits.manage', 'suppliers.manage', 'receipts.manage',
                    'attendance.view', 'attendance.manage',
                    'finance.view', 'finance.manage',
                    'inbox.view', 'inbox.manage', 'campaigns.manage',
                    'analytics.view', 'portal.manage',
                    'loyalty.view', 'loyalty.manage',
                ],
            ],
            'agent' => [
                'name' => 'Sales Agent',
                'permissions' => [
                    'leads.view', 'leads.manage', 'leads.assign',
                    'companies.view',
                    'folders.view', 'folders.manage',
                    'dashboard.view',
                    'pipeline.view',
                    'contacts.view',
                    'deposits.manage',
                    'attendance.view',
                    'inbox.view', 'inbox.manage',
                    'analytics.view',
                    'loyalty.view',
                ],
            ],
            'accountant' => [
                'name' => 'Accountant',
                'permissions' => [
                    'folders.view',
                    'payments.view', 'payments.process',
                    'dashboard.view',
                    'commissions.view', 'deposits.manage',
                    'receipts.manage',
                    'finance.view', 'finance.manage',
                ],
            ],
        ];

        foreach ($roleDefinitions as $slug => $def) {
            $role = Role::firstOrCreate(
                ['slug' => $slug, 'tenant_id' => null],
                ['name' => $def['name'], 'is_system' => true]
            );

            $permissionIds = $def['permissions'] === ['*']
                ? $allPermissionIds->values()->all()
                : collect($def['permissions'])->map(fn ($s) => $allPermissionIds[$s])->filter()->all();

            $role->permissions()->sync($permissionIds);
        }

        $tenant = Tenant::firstOrCreate(
            ['slug' => config('saas.legacy_tenant_slug', 'default')],
            [
                'name' => 'Default Organization',
                'email' => 'admin@example.com',
                'status' => 'active',
                'plan' => 'enterprise',
                'trial_ends_at' => null,
                'billing_status' => 'active',
                'payment_terms_days' => (int) config('saas.billing.default_payment_terms_days', 30),
            ]
        );

        app(PipelineService::class)->seedDefaultStagesForTenant($tenant->id);
        app(ChartOfAccountsService::class)->seedForTenant($tenant->id);
    }
}
