<?php

namespace App\Services\Integrations;

use App\Models\IntegrationSyncLog;
use App\Models\TenantIntegration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class TenantIntegrationService
{
    public function __construct(
        private readonly IntegrationRegistry $registry,
    ) {}

    /**
     * @param  array<string, string>  $credentials
     */
    public function store(int $tenantId, int $providerId, array $credentials, ?array $settings = null, ?string $label = null): TenantIntegration
    {
        $encrypted = Crypt::encryptString(json_encode($credentials));

        return TenantIntegration::updateOrCreate(
            ['tenant_id' => $tenantId, 'provider_id' => $providerId],
            [
                'label' => $label,
                'credentials' => $encrypted,
                'settings' => $settings,
                'status' => 'inactive',
                'last_error' => null,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function decryptCredentials(TenantIntegration $integration): array
    {
        if (! $integration->credentials) {
            return [];
        }

        try {
            $decoded = json_decode(Crypt::decryptString($integration->credentials), true);

            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function test(TenantIntegration $integration): array
    {
        $adapter = $this->registry->adapterFor($integration);
        $ok = $adapter->testConnection($integration);

        $integration->update([
            'status' => $ok ? 'active' : 'error',
            'last_error' => $ok ? null : 'Connection test failed — check credentials.',
            'last_synced_at' => $ok ? now() : $integration->last_synced_at,
        ]);

        $this->logSync($integration, 'test', $ok ? 'success' : 'failed');

        return ['success' => $ok, 'status' => $integration->fresh()->status];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function searchFlights(TenantIntegration $integration, array $params): array
    {
        $adapter = $this->registry->adapterFor($integration);
        $result = $adapter->searchFlights($integration, $params);

        $this->logSync($integration, 'search', 'success', ['type' => 'flights'], $result, count($result['results'] ?? []));

        return $result;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function searchHotels(TenantIntegration $integration, array $params): array
    {
        $adapter = $this->registry->adapterFor($integration);
        $result = $adapter->searchHotels($integration, $params);

        $this->logSync($integration, 'search', 'success', ['type' => 'hotels'], $result, count($result['results'] ?? []));

        return $result;
    }

    private function logSync(
        TenantIntegration $integration,
        string $type,
        string $status,
        ?array $request = null,
        ?array $response = null,
        int $records = 0,
        ?string $error = null,
    ): void {
        IntegrationSyncLog::create([
            'tenant_integration_id' => $integration->id,
            'sync_type' => $type,
            'status' => $status,
            'request_summary' => $request,
            'response_summary' => $response ? ['records' => count($response['results'] ?? [])] : null,
            'records_processed' => $records,
            'error_message' => $error,
            'created_at' => now(),
        ]);
    }

    public function runSync(TenantIntegration $integration, string $syncType = 'import'): int
    {
        return DB::transaction(function () use ($integration, $syncType) {
            $integration->update([
                'last_synced_at' => now(),
                'status' => 'active',
                'last_error' => null,
            ]);

            $this->logSync($integration, $syncType, 'success', null, ['stub' => true], 0);

            return 0;
        });
    }
}
