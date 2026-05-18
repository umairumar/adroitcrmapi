<?php

namespace Tests\Unit;

use App\Services\Tenant\TenantContext;
use PHPUnit\Framework\TestCase;

class TenantContextTest extends TestCase
{
    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_tenant_id_and_bypass(): void
    {
        TenantContext::set(42);
        $this->assertSame(42, TenantContext::id());
        $this->assertFalse(TenantContext::shouldBypass());

        TenantContext::enableBypass();
        $this->assertTrue(TenantContext::shouldBypass());

        TenantContext::clear();
        $this->assertNull(TenantContext::id());
        $this->assertFalse(TenantContext::shouldBypass());
    }
}
