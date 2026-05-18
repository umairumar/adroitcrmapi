<?php

namespace App\Services\Integrations\Adapters;

use App\Models\TenantIntegration;
use App\Services\Integrations\Concerns\DecryptsIntegrationCredentials;
use App\Services\Integrations\Contracts\TravelSearchAdapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AmadeusAdapter implements TravelSearchAdapter
{
    use DecryptsIntegrationCredentials;

    public function testConnection(TenantIntegration $integration): bool
    {
        return $this->accessToken($integration) !== null;
    }

    public function searchFlights(TenantIntegration $integration, array $params): array
    {
        $token = $this->accessToken($integration);
        if (! $token) {
            throw new \RuntimeException('Amadeus authentication failed');
        }

        $base = $this->baseUrl($integration);
        $query = [
            'originLocationCode' => strtoupper($params['origin'] ?? ''),
            'destinationLocationCode' => strtoupper($params['destination'] ?? ''),
            'departureDate' => $params['departure_date'] ?? '',
            'adults' => (int) ($params['adults'] ?? 1),
            'max' => (int) ($params['max'] ?? 10),
            'currencyCode' => $params['currency'] ?? 'GBP',
        ];

        if (! empty($params['return_date'])) {
            $query['returnDate'] = $params['return_date'];
        }

        $response = Http::withToken($token)
            ->timeout(30)
            ->get("{$base}/v2/shopping/flight-offers", $query);

        if (! $response->successful()) {
            throw new \RuntimeException('Amadeus flight search failed: ' . $response->body());
        }

        $data = $response->json('data', []);
        $results = [];

        foreach ($data as $offer) {
            $itinerary = $offer['itineraries'][0] ?? null;
            $segment = $itinerary['segments'][0] ?? null;
            if (! $segment) {
                continue;
            }

            $results[] = [
                'id' => $offer['id'] ?? Str::uuid()->toString(),
                'carrier' => $segment['carrierCode'] ?? null,
                'flight_number' => ($segment['carrierCode'] ?? '') . ($segment['number'] ?? ''),
                'origin' => $segment['departure']['iataCode'] ?? null,
                'destination' => collect($itinerary['segments'] ?? [])->last()['arrival']['iataCode'] ?? null,
                'departure' => $segment['departure']['at'] ?? null,
                'duration' => $itinerary['duration'] ?? null,
                'price' => [
                    'amount' => (float) ($offer['price']['grandTotal'] ?? 0),
                    'currency' => $offer['price']['currency'] ?? 'GBP',
                ],
                'raw' => $offer,
            ];
        }

        return [
            'provider' => 'amadeus',
            'stub' => false,
            'query' => $query,
            'results' => $results,
        ];
    }

    public function searchHotels(TenantIntegration $integration, array $params): array
    {
        $token = $this->accessToken($integration);
        if (! $token) {
            throw new \RuntimeException('Amadeus authentication failed');
        }

        $base = $this->baseUrl($integration);
        $city = $params['city'] ?? '';

        $hotelIdsResponse = Http::withToken($token)
            ->timeout(30)
            ->get("{$base}/v1/reference-data/locations/hotels/by-city", [
                'cityCode' => strtoupper(substr($city, 0, 3)),
            ]);

        $hotelIds = collect($hotelIdsResponse->json('data', []))
            ->pluck('hotelId')
            ->filter()
            ->take(20)
            ->values()
            ->all();

        if ($hotelIds === []) {
            return [
                'provider' => 'amadeus',
                'stub' => false,
                'query' => $params,
                'results' => [],
                'message' => 'No hotels found for city code. Use IATA city code (e.g. DXB, LON).',
            ];
        }

        $offersResponse = Http::withToken($token)
            ->timeout(30)
            ->get("{$base}/v3/shopping/hotel-offers", [
                'hotelIds' => implode(',', $hotelIds),
                'checkInDate' => $params['check_in'],
                'checkOutDate' => $params['check_out'],
                'adults' => (int) ($params['guests'] ?? 2),
            ]);

        if (! $offersResponse->successful()) {
            throw new \RuntimeException('Amadeus hotel search failed: ' . $offersResponse->body());
        }

        $results = [];
        foreach ($offersResponse->json('data', []) as $item) {
            $hotel = $item['hotel'] ?? [];
            $offer = $item['offers'][0] ?? [];
            $results[] = [
                'id' => $hotel['hotelId'] ?? null,
                'name' => $hotel['name'] ?? 'Hotel',
                'city' => $city,
                'price_per_night' => [
                    'amount' => (float) ($offer['price']['total'] ?? 0),
                    'currency' => $offer['price']['currency'] ?? 'GBP',
                ],
            ];
        }

        return [
            'provider' => 'amadeus',
            'stub' => false,
            'query' => $params,
            'results' => $results,
        ];
    }

    private function accessToken(TenantIntegration $integration): ?string
    {
        $creds = $this->credentials($integration);
        $clientId = $creds['client_id'] ?? null;
        $clientSecret = $creds['client_secret'] ?? null;

        if (! $clientId || ! $clientSecret) {
            return null;
        }

        $cacheKey = 'amadeus.token.' . $integration->id;

        return Cache::remember($cacheKey, 1700, function () use ($integration, $clientId, $clientSecret) {
            $base = $this->baseUrl($integration);
            $response = Http::asForm()
                ->timeout(15)
                ->post("{$base}/v1/security/oauth2/token", [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);

            if (! $response->successful()) {
                return null;
            }

            return $response->json('access_token');
        });
    }

    private function baseUrl(TenantIntegration $integration): string
    {
        $creds = $this->credentials($integration);
        $env = strtolower($creds['environment'] ?? 'test');

        return $env === 'production'
            ? config('integrations.amadeus.production_base_url')
            : config('integrations.amadeus.test_base_url');
    }
}
