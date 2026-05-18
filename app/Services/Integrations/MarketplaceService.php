<?php

namespace App\Services\Integrations;

use App\Models\ApiUsageLog;
use App\Models\MarketplaceApp;
use App\Models\TenantApiApp;
use App\Models\TenantApiKey;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MarketplaceService
{
    public function catalog(bool $publicOnly = true): Collection
    {
        $q = MarketplaceApp::query()->where('is_active', true);
        if ($publicOnly) {
            $q->where('is_public', true);
        }

        return $q->orderBy('category')->orderBy('name')->get();
    }

    public function subscribe(int $tenantId, string $appSlug, ?array $scopes = null): TenantApiApp
    {
        $app = MarketplaceApp::where('slug', $appSlug)->where('is_active', true)->firstOrFail();

        return TenantApiApp::firstOrCreate(
            ['tenant_id' => $tenantId, 'marketplace_app_id' => $app->id],
            [
                'name' => $app->name,
                'scopes' => $scopes ?? $app->scopes,
                'status' => 'active',
                'subscribed_at' => now(),
            ]
        );
    }

    /**
     * @return array{key: string, record: TenantApiKey}
     */
    public function issueKey(TenantApiApp $app, string $name): array
    {
        $prefix = config('integrations.marketplace.key_prefix', 'adc_');
        $plain = $prefix . Str::random(40);

        $ttlDays = (int) config('integrations.marketplace.key_ttl_days', 0);
        $expires = $ttlDays > 0 ? now()->addDays($ttlDays) : null;

        $record = TenantApiKey::create([
            'tenant_api_app_id' => $app->id,
            'name' => $name,
            'key_prefix' => substr($plain, 0, 12),
            'key_hash' => Hash::make($plain),
            'expires_at' => $expires,
        ]);

        return ['key' => $plain, 'record' => $record];
    }

    public function revokeKey(TenantApiKey $key): void
    {
        $key->update(['revoked_at' => now()]);
    }

    public function authenticate(string $plainKey): ?TenantApiKey
    {
        if (strlen($plainKey) < 16) {
            return null;
        }

        $prefix = substr($plainKey, 0, 12);

        $candidates = TenantApiKey::where('key_prefix', $prefix)
            ->whereNull('revoked_at')
            ->with(['app.marketplaceApp', 'app.tenant'])
            ->get();

        foreach ($candidates as $key) {
            if (! $key->isValid()) {
                continue;
            }
            if (Hash::check($plainKey, $key->key_hash)) {
                $key->update(['last_used_at' => now()]);

                return $key;
            }
        }

        return null;
    }

    public function hasScope(TenantApiKey $key, string $scope): bool
    {
        $scopes = $key->app?->effectiveScopes() ?? [];

        return in_array($scope, $scopes, true) || in_array('*', $scopes, true);
    }

    public function logUsage(TenantApiKey $key, string $endpoint, string $method, int $status, ?string $ip): void
    {
        ApiUsageLog::create([
            'tenant_api_key_id' => $key->id,
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $status,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }

    public function rateLimitExceeded(TenantApiKey $key): bool
    {
        $limit = $key->app?->marketplaceApp?->rate_limit_per_minute
            ?? config('integrations.marketplace.default_rate_limit', 60);

        $count = ApiUsageLog::where('tenant_api_key_id', $key->id)
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        return $count >= $limit;
    }

    public function tenantSubscriptions(int $tenantId): Collection
    {
        return TenantApiApp::with(['marketplaceApp', 'apiKeys'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->get();
    }
}
