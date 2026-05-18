<?php

namespace App\Services\Integrations;

use App\Models\IntegrationProvider;
use App\Models\TenantIntegration;
use App\Services\Integrations\Adapters\AmadeusAdapter;
use App\Services\Integrations\Adapters\HotelbedsAdapter;
use App\Services\Integrations\Adapters\StubTravelAdapter;
use App\Services\Integrations\Contracts\TravelSearchAdapter;
use Illuminate\Support\Collection;

class IntegrationRegistry
{
    public function __construct(
        private readonly StubTravelAdapter $stubAdapter,
        private readonly AmadeusAdapter $amadeusAdapter,
        private readonly HotelbedsAdapter $hotelbedsAdapter,
    ) {}

    public function listProviders(): Collection
    {
        return IntegrationProvider::where('is_active', true)->orderBy('name')->get();
    }

    public function adapterFor(TenantIntegration $integration): TravelSearchAdapter
    {
        return match ($integration->provider?->slug) {
            'amadeus' => $this->amadeusAdapter,
            'hotelbeds' => $this->hotelbedsAdapter,
            default => $this->stubAdapter,
        };
    }

    public function providerBySlug(string $slug): ?IntegrationProvider
    {
        return IntegrationProvider::where('slug', $slug)->where('is_active', true)->first();
    }
}
