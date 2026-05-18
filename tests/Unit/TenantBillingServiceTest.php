<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Services\Billing\TenantBillingService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class TenantBillingServiceTest extends TestCase
{
    public function test_active_trial_allows_access(): void
    {
        $tenant = new Tenant([
            'status' => 'active',
            'plan' => 'trial',
            'trial_ends_at' => Carbon::now()->addDays(5),
            'billing_status' => 'trial',
        ]);

        $billing = new TenantBillingService;

        $this->assertTrue($billing->canAccessPlatform($tenant));
        $this->assertNull($billing->billingBlockReason($tenant));
    }

    public function test_expired_trial_without_invoice_blocks(): void
    {
        $tenant = new Tenant([
            'status' => 'active',
            'plan' => 'trial',
            'trial_ends_at' => Carbon::now()->subDay(),
            'billing_status' => 'trial',
        ]);

        $billing = new TenantBillingService;

        $this->assertFalse($billing->canAccessPlatform($tenant));
        $this->assertSame('trial_expired', $billing->billingBlockReason($tenant));
    }
}
