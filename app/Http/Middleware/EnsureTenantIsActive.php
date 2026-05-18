<?php

namespace App\Http\Middleware;

use App\Services\Billing\TenantBillingService;
use App\Services\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function __construct(
        private readonly TenantBillingService $billing,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (TenantContext::shouldBypass()) {
            return $next($request);
        }

        $tenant = TenantContext::tenant();

        if (! $tenant) {
            return $next($request);
        }

        $this->billing->syncTenantBillingStatus($tenant);
        $tenant->refresh();

        if (! $this->billing->canAccessPlatform($tenant)) {
            $code = $this->billing->billingBlockReason($tenant) ?? 'billing_inactive';

            $messages = [
                'trial_expired' => 'Your trial has ended. A platform invoice is required to continue. Please contact billing.',
                'invoice_overdue' => 'Your account has overdue invoices. Please settle payment to restore access.',
                'account_suspended' => 'Your organization account is suspended due to billing. Please contact support.',
                'account_inactive' => 'Your organization account is not active.',
                'billing_inactive' => 'Billing is not active for this organization. Please contact support.',
            ];

            return response()->json([
                'status' => false,
                'message' => $messages[$code] ?? $messages['billing_inactive'],
                'code' => $code,
            ], 402);
        }

        return $next($request);
    }
}
