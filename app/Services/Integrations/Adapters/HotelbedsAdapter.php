<?php

namespace App\Services\Integrations\Adapters;

use App\Models\TenantIntegration;
use App\Services\Integrations\Concerns\DecryptsIntegrationCredentials;
use App\Services\Integrations\Contracts\TravelSearchAdapter;
use Illuminate\Support\Facades\Http;

class HotelbedsAdapter implements TravelSearchAdapter
{
    use DecryptsIntegrationCredentials;

    public function testConnection(TenantIntegration $integration): bool
    {
        $creds = $this->credentials($integration);

        return ! empty($creds['api_key']) && ! empty($creds['secret']);
    }

    public function searchFlights(TenantIntegration $integration, array $params): array
    {
        throw new \InvalidArgumentException('Hotelbeds does not support flight search');
    }

    public function searchHotels(TenantIntegration $integration, array $params): array
    {
        $creds = $this->credentials($integration);
        $apiKey = $creds['api_key'] ?? '';
        $secret = $creds['secret'] ?? '';

        if (! $apiKey || ! $secret) {
            throw new \RuntimeException('Hotelbeds credentials missing');
        }

        $base = $this->baseUrl($integration);
        $timestamp = time();
        $signature = hash('sha256', $apiKey . $secret . $timestamp);

        $body = [
            'stay' => [
                'checkIn' => $params['check_in'],
                'checkOut' => $params['check_out'],
            ],
            'occupancies' => [
                ['rooms' => 1, 'adults' => (int) ($params['guests'] ?? 2), 'children' => 0],
            ],
            'destination' => [
                'code' => strtoupper($params['city'] ?? ''),
            ],
        ];

        $response = Http::withHeaders([
            'Api-key' => $apiKey,
            'X-Signature' => $signature,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->timeout(30)
            ->post("{$base}/hotel-api/1.0/hotels", $body);

        if (! $response->successful()) {
            throw new \RuntimeException('Hotelbeds search failed: ' . $response->body());
        }

        $hotels = $response->json('hotels.hotels', []);
        $results = [];

        foreach ($hotels as $hotel) {
            $minRate = $hotel['minRate'] ?? null;
            $results[] = [
                'id' => $hotel['code'] ?? null,
                'name' => $hotel['name'] ?? 'Hotel',
                'city' => $params['city'] ?? null,
                'stars' => $hotel['categoryCode'] ?? null,
                'price_per_night' => [
                    'amount' => (float) $minRate,
                    'currency' => $hotel['currency'] ?? 'EUR',
                ],
            ];
        }

        return [
            'provider' => 'hotelbeds',
            'stub' => false,
            'query' => $params,
            'results' => $results,
        ];
    }

    private function baseUrl(TenantIntegration $integration): string
    {
        $creds = $this->credentials($integration);
        $env = strtolower($creds['environment'] ?? 'test');

        return $env === 'production'
            ? config('integrations.hotelbeds.production_base_url')
            : config('integrations.hotelbeds.test_base_url');
    }
}
