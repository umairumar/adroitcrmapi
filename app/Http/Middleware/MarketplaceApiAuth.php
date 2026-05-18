<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Integrations\MarketplaceService;
use App\Services\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarketplaceApiAuth
{
    public function __construct(
        private readonly MarketplaceService $marketplace,
    ) {}

    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $apiKey = $request->bearerToken() ?? $request->header('X-Api-Key');

        if (! $apiKey) {
            return response()->json(['status' => false, 'message' => 'API key required'], 401);
        }

        $key = $this->marketplace->authenticate($apiKey);
        if (! $key || ! $key->app) {
            return response()->json(['status' => false, 'message' => 'Invalid API key'], 401);
        }

        if ($this->marketplace->rateLimitExceeded($key)) {
            return response()->json(['status' => false, 'message' => 'Rate limit exceeded'], 429);
        }

        if ($scope && ! $this->marketplace->hasScope($key, $scope)) {
            return response()->json(['status' => false, 'message' => 'Insufficient scope'], 403);
        }

        $tenant = Tenant::find($key->app->tenant_id);
        if ($tenant) {
            TenantContext::setFromTenant($tenant);
            TenantContext::disableBypass();
        }

        $request->attributes->set('marketplace_api_key', $key);
        $request->attributes->set('marketplace_app', $key->app);

        $response = $next($request);

        $this->marketplace->logUsage(
            $key,
            $request->path(),
            $request->method(),
            $response->getStatusCode(),
            $request->ip(),
        );

        return $response;
    }
}
