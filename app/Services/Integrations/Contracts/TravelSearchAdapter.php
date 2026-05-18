<?php

namespace App\Services\Integrations\Contracts;

use App\Models\TenantIntegration;

interface TravelSearchAdapter
{
    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function searchFlights(TenantIntegration $integration, array $params): array;

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function searchHotels(TenantIntegration $integration, array $params): array;

    public function testConnection(TenantIntegration $integration): bool;
}
