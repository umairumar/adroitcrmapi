<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        TenantContext::clear();

        $user = $request->user();

        if ($user) {
            if ($user->isPlatformAdmin()) {
                TenantContext::enableBypass();
            }

            if ($user->tenant_id) {
                $tenant = Tenant::find($user->tenant_id);
                if ($tenant) {
                    TenantContext::setFromTenant($tenant);
                } else {
                    TenantContext::set((int) $user->tenant_id);
                }
            }

            if ($request->header('X-Tenant-Id') && $user->isPlatformAdmin()) {
                $overrideId = (int) $request->header('X-Tenant-Id');
                $tenant = Tenant::find($overrideId);
                if ($tenant) {
                    TenantContext::disableBypass();
                    TenantContext::setFromTenant($tenant);
                }
            }
        }

        return $next($request);
    }
}
