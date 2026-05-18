<?php

namespace App\Http\Middleware;

use App\Services\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (TenantContext::shouldBypass()) {
            return $next($request);
        }

        $tenant = TenantContext::tenant();

        if (! $tenant) {
            return $next($request);
        }

        if ($tenant->status !== 'active') {
            return response()->json([
                'status' => false,
                'message' => 'Your organization account is not active. Please contact support.',
            ], 403);
        }

        if ($tenant->plan === 'trial' && $tenant->trial_ends_at && $tenant->trial_ends_at->isPast()) {
            return response()->json([
                'status' => false,
                'message' => 'Your trial has expired. Please upgrade your subscription.',
                'code' => 'trial_expired',
            ], 402);
        }

        return $next($request);
    }
}
