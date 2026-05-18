<?php

namespace App\Services\Auth;

use App\Models\CrmFolders;
use App\Models\CrmLead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AuthorizationService
{
    public function __construct(
        private readonly BranchAccess $branchAccess,
    ) {}

    public function hasPermission(User $user, string $permission): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->hasPermission($permission)) {
            return true;
        }

        $fallbacks = [
            'pipeline.view' => ['leads.view'],
            'pipeline.manage' => ['leads.manage'],
            'contacts.view' => ['leads.view'],
            'contacts.manage' => ['leads.manage'],
            'commissions.view' => ['folders.view'],
            'commissions.manage' => ['folders.manage'],
            'deposits.manage' => ['folders.manage', 'payments.process'],
            'suppliers.manage' => ['folders.manage'],
            'receipts.manage' => ['payments.process'],
            'attendance.view' => ['users.view'],
            'attendance.manage' => ['users.manage'],
            'finance.view' => ['payments.view', 'payments.process'],
            'finance.manage' => ['payments.process', 'folders.manage'],
        ];

        foreach ($fallbacks[$permission] ?? [] as $alt) {
            if ($user->hasPermission($alt)) {
                return true;
            }
        }

        return false;
    }

    public function roleSlug(User $user): string
    {
        if ($user->isPlatformAdmin()) {
            return 'platform_admin';
        }

        $role = $user->roles()->first();
        if ($role) {
            return $role->slug;
        }

        return config('saas.utype_role_map.' . ($user->utype ?? ''), 'agent');
    }

    public function isTenantAdmin(User $user): bool
    {
        return in_array($this->roleSlug($user), ['platform_admin', 'tenant_admin', 'sadmin', 'cadmin'], true);
    }

    public function scopeLeads(Builder $query, User $user): Builder
    {
        if ($user->isPlatformAdmin()) {
            return $query;
        }

        $role = $this->roleSlug($user);

        if ($role === 'tenant_admin' || $user->utype === 'cadmin') {
            return $query->where('mby', $user->id)
                ->tap(fn ($q) => $this->branchAccess->scopeLegacyCompanyColumn($q, 'company', $user));
        }

        if ($role === 'agent' || $user->utype === 'agent') {
            return $query->where('cby', $user->id)
                ->tap(fn ($q) => $this->branchAccess->scopeLegacyCompanyColumn($q, 'company', $user));
        }

        return $query->where('agent', $user->id)
            ->tap(fn ($q) => $this->branchAccess->scopeLegacyCompanyColumn($q, 'company', $user));
    }

    public function scopeFolders(Builder $query, User $user): Builder
    {
        if ($user->isPlatformAdmin()) {
            return $query;
        }

        $role = $this->roleSlug($user);

        if (in_array($role, ['tenant_admin', 'agent'], true) || in_array($user->utype, ['cadmin', 'agent'], true)) {
            return $query->tap(fn ($q) => $this->branchAccess->scopeLegacyCompanyColumn($q, 'company', $user));
        }

        return $query->where('booked_by', $user->id);
    }

    public function canManageUsers(User $user): bool
    {
        return $this->hasPermission($user, 'users.manage')
            || in_array($this->roleSlug($user), ['platform_admin', 'tenant_admin'], true);
    }

    public function canManageCompanies(User $user): bool
    {
        return $this->hasPermission($user, 'companies.manage')
            || in_array($this->roleSlug($user), ['platform_admin', 'tenant_admin'], true);
    }

    public function canProcessPayments(User $user): bool
    {
        return $this->hasPermission($user, 'payments.process')
            || in_array($this->roleSlug($user), ['platform_admin', 'tenant_admin', 'accountant'], true)
            || $user->utype === 'Accountant';
    }

    public function assertLeadAccessible(User $user, CrmLead $lead): void
    {
        if ($user->isPlatformAdmin()) {
            return;
        }

        if ($lead->tenant_id && $user->tenant_id && (int) $lead->tenant_id !== (int) $user->tenant_id) {
            abort(403, 'Lead belongs to another organization.');
        }

        $scoped = $this->scopeLeads(CrmLead::query()->where('id', $lead->id), $user);

        if (! $scoped->exists()) {
            abort(403, 'You do not have access to this lead.');
        }
    }

    public function assertFolderAccessible(User $user, CrmFolders $folder): void
    {
        if ($user->isPlatformAdmin()) {
            return;
        }

        if ($folder->tenant_id && $user->tenant_id && (int) $folder->tenant_id !== (int) $user->tenant_id) {
            abort(403, 'Booking belongs to another organization.');
        }

        $scoped = $this->scopeFolders(CrmFolders::query()->where('id', $folder->id), $user);

        if (! $scoped->exists()) {
            abort(403, 'You do not have access to this booking.');
        }
    }
}
