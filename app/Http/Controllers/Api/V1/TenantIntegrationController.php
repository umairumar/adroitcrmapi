<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TenantIntegration;
use App\Services\Auth\AuthorizationService;
use App\Services\Integrations\IntegrationRegistry;
use App\Services\Integrations\TenantIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TenantIntegrationController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly IntegrationRegistry $registry,
        private readonly TenantIntegrationService $integrations,
    ) {}

    public function providers(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'integrations.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        return response()->json([
            'status' => true,
            'data' => $this->registry->listProviders(),
        ]);
    }

    public function index(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'integrations.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        return response()->json([
            'status' => true,
            'data' => TenantIntegration::with('provider')->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'integrations.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'provider_slug' => 'required|string',
            'label' => 'nullable|string|max:120',
            'credentials' => 'required|array',
            'settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $provider = $this->registry->providerBySlug($request->provider_slug);
        if (! $provider) {
            return response()->json(['status' => false, 'message' => 'Unknown provider'], 422);
        }

        $integration = $this->integrations->store(
            (int) $request->user()->tenant_id,
            $provider->id,
            $request->credentials,
            $request->settings,
            $request->label,
        );

        return response()->json([
            'status' => true,
            'data' => $integration->load('provider'),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'integrations.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $integration = TenantIntegration::with('provider')->findOrFail($id);

        if ($request->has('credentials')) {
            $this->integrations->store(
                (int) $integration->tenant_id,
                $integration->provider_id,
                $request->credentials,
                $request->settings ?? $integration->settings,
                $request->label ?? $integration->label,
            );
            $integration->refresh();
        } else {
            $integration->update($request->only(['label', 'settings', 'status']));
        }

        return response()->json(['status' => true, 'data' => $integration->load('provider')]);
    }

    public function destroy(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'integrations.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        TenantIntegration::findOrFail($id)->delete();

        return response()->json(['status' => true, 'message' => 'Integration removed']);
    }

    public function test(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'integrations.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $integration = TenantIntegration::with('provider')->findOrFail($id);
        $result = $this->integrations->test($integration);

        return response()->json(['status' => true, 'data' => $result]);
    }

    public function syncLogs(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'integrations.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $integration = TenantIntegration::findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $integration->syncLogs()->orderByDesc('id')->limit(50)->get(),
        ]);
    }

    public function searchFlights(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'integrations.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate([
            'integration_id' => 'required|integer',
            'origin' => 'required|string|size:3',
            'destination' => 'required|string|size:3',
            'departure_date' => 'required|date',
            'return_date' => 'nullable|date',
            'adults' => 'nullable|integer|min:1',
        ]);

        $integration = TenantIntegration::with('provider')->findOrFail($request->integration_id);

        if ($integration->status !== 'active') {
            return response()->json(['status' => false, 'message' => 'Integration is not active — run test first'], 422);
        }

        return response()->json([
            'status' => true,
            'data' => $this->integrations->searchFlights($integration, $request->only([
                'origin', 'destination', 'departure_date', 'return_date', 'adults',
            ])),
        ]);
    }

    public function searchHotels(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'integrations.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate([
            'integration_id' => 'required|integer',
            'city' => 'required|string',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'nullable|integer|min:1',
        ]);

        $integration = TenantIntegration::with('provider')->findOrFail($request->integration_id);

        if ($integration->status !== 'active') {
            return response()->json(['status' => false, 'message' => 'Integration is not active — run test first'], 422);
        }

        return response()->json([
            'status' => true,
            'data' => $this->integrations->searchHotels($integration, $request->only([
                'city', 'check_in', 'check_out', 'guests',
            ])),
        ]);
    }
}
