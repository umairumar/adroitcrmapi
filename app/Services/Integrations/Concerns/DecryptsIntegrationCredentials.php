<?php

namespace App\Services\Integrations\Concerns;

use App\Models\TenantIntegration;
use Illuminate\Support\Facades\Crypt;

trait DecryptsIntegrationCredentials
{
    /**
     * @return array<string, mixed>
     */
    protected function credentials(TenantIntegration $integration): array
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
}
