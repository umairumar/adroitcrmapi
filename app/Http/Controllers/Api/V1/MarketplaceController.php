<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TenantApiKey;
use App\Services\Auth\AuthorizationService;
use App\Services\Integrations\MarketplaceService;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly MarketplaceService $marketplace,
    ) {}

    public function catalog()
    {
        return response()->json([
            'status' => true,
            'data' => $this->marketplace->catalog(),
        ]);
    }

    public function subscriptions(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'marketplace.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        return response()->json([
            'status' => true,
            'data' => $this->marketplace->tenantSubscriptions((int) $request->user()->tenant_id),
        ]);
    }

    public function subscribe(Request $request, string $slug)
    {
        if (! $this->authz->hasPermission($request->user(), 'marketplace.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $app = $this->marketplace->subscribe(
            (int) $request->user()->tenant_id,
            $slug,
            $request->scopes,
        );

        return response()->json(['status' => true, 'data' => $app->load('marketplaceApp')], 201);
    }

    public function issueKey(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'marketplace.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate([
            'tenant_api_app_id' => 'required|integer',
            'name' => 'required|string|max:120',
        ]);

        $app = \App\Models\TenantApiApp::findOrFail($request->tenant_api_app_id);
        $issued = $this->marketplace->issueKey($app, $request->name);

        return response()->json([
            'status' => true,
            'data' => [
                'key' => $issued['key'],
                'api_key' => $issued['record'],
            ],
            'message' => 'Store this key securely — it will not be shown again.',
        ], 201);
    }

    public function revokeKey(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'marketplace.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $key = TenantApiKey::findOrFail($id);
        $this->marketplace->revokeKey($key);

        return response()->json(['status' => true, 'message' => 'API key revoked']);
    }
}
