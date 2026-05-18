<?php

namespace App\Services\Integrations\Adapters;

use App\Models\TenantIntegration;
use App\Services\Integrations\Contracts\TravelSearchAdapter;
use Illuminate\Support\Facades\Crypt;

class StubTravelAdapter implements TravelSearchAdapter
{

    public function searchFlights(TenantIntegration $integration, array $params): array
    {
        $provider = $integration->provider?->slug ?? 'unknown';

        return [
            'provider' => $provider,
            'stub' => true,
            'message' => 'Configure live GDS credentials to enable real flight search.',
            'query' => [
                'origin' => $params['origin'] ?? null,
                'destination' => $params['destination'] ?? null,
                'departure_date' => $params['departure_date'] ?? null,
                'return_date' => $params['return_date'] ?? null,
                'adults' => (int) ($params['adults'] ?? 1),
            ],
            'results' => [
                [
                    'id' => 'stub-1',
                    'carrier' => 'XX',
                    'flight_number' => '100',
                    'origin' => $params['origin'] ?? 'LHR',
                    'destination' => $params['destination'] ?? 'DXB',
                    'departure' => ($params['departure_date'] ?? now()->addDays(14)->toDateString()) . ' 09:00',
                    'price' => ['amount' => 450.00, 'currency' => 'GBP'],
                ],
            ],
        ];
    }

    public function searchHotels(TenantIntegration $integration, array $params): array
    {
        $provider = $integration->provider?->slug ?? 'unknown';

        return [
            'provider' => $provider,
            'stub' => true,
            'message' => 'Configure live OTA credentials to enable real hotel search.',
            'query' => [
                'city' => $params['city'] ?? null,
                'check_in' => $params['check_in'] ?? null,
                'check_out' => $params['check_out'] ?? null,
                'guests' => (int) ($params['guests'] ?? 2),
            ],
            'results' => [
                [
                    'id' => 'stub-h1',
                    'name' => 'Sample Hotel',
                    'city' => $params['city'] ?? 'Dubai',
                    'stars' => 4,
                    'price_per_night' => ['amount' => 120.00, 'currency' => 'GBP'],
                ],
            ],
        ];
    }

    public function testConnection(TenantIntegration $integration): bool
    {
        if (! $integration->credentials) {
            return false;
        }

        try {
            $decoded = json_decode(Crypt::decryptString($integration->credentials), true);

            return is_array($decoded) && $decoded !== [];
        } catch (\Throwable) {
            return false;
        }
    }
}
