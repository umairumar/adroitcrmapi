<?php

namespace App\Services\Tenant;

use App\Models\Tenant;

class TenantContext
{
    private static ?int $tenantId = null;

    private static ?Tenant $tenant = null;

    private static bool $bypass = false;

    public static function set(?int $tenantId, ?Tenant $tenant = null): void
    {
        self::$tenantId = $tenantId;
        self::$tenant = $tenant;
    }

    public static function setFromTenant(Tenant $tenant): void
    {
        self::$tenantId = $tenant->id;
        self::$tenant = $tenant;
    }

    public static function id(): ?int
    {
        return self::$tenantId;
    }

    public static function tenant(): ?Tenant
    {
        return self::$tenant;
    }

    public static function enableBypass(): void
    {
        self::$bypass = true;
    }

    public static function disableBypass(): void
    {
        self::$bypass = false;
    }

    public static function shouldBypass(): bool
    {
        return self::$bypass;
    }

    public static function clear(): void
    {
        self::$tenantId = null;
        self::$tenant = null;
        self::$bypass = false;
    }
}
