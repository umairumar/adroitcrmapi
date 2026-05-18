<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
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
                ],
            ],
            'agent' => [
                'name' => 'Sales Agent',
                'permissions' => [
                    'leads.view', 'leads.manage',
                    'companies.view',
                    'folders.view', 'folders.manage',
                    'dashboard.view',
                ],
            ],
            'accountant' => [
                'name' => 'Accountant',
                'permissions' => [
                    'folders.view',
                    'payments.view', 'payments.process',
                    'dashboard.view',
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

        Tenant::firstOrCreate(
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
    }
}
