<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\Integrations\WhiteLabelService;
use Illuminate\Http\Request;

/**
 * Public bootstrap payload for SPA / mobile clients.
 */
class AppBootstrapController extends Controller
{
    public function __construct(
        private readonly WhiteLabelService $whiteLabel,
    ) {}

    public function show(Request $request)
    {
        $slug = $request->query('tenant') ?? $request->query('tenant_slug');
        $domain = $request->query('domain');

        if (! $slug && $domain) {
            $branding = $this->whiteLabel->resolvePublic($domain);
            if ($branding) {
                return response()->json([
                    'status' => true,
                    'data' => $this->buildPayload($branding),
                ]);
            }
        }

        if (! $slug) {
            return response()->json([
                'status' => false,
                'message' => 'tenant or domain query parameter required',
            ], 422);
        }

        $branding = $this->whiteLabel->resolvePublic($slug);
        if (! $branding) {
            return response()->json(['status' => false, 'message' => 'Tenant not found'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->buildPayload($branding),
        ]);
    }

    /**
     * @param  array<string, mixed>  $branding
     * @return array<string, mixed>
     */
    private function buildPayload(array $branding): array
    {
        $tenant = Tenant::where('slug', $branding['tenant']['slug'] ?? '')->first();

        return [
            'tenant' => $branding['tenant'],
            'branding' => $branding['branding'],
            'api' => [
                'base_path' => '/api/v1',
                'auth_login' => '/api/v1/login',
                'portal_auth' => '/api/v1/portal/auth/{token}',
                'lead_capture' => '/api/v1/leadsdirectstore',
                'public_branding' => '/api/v1/branding/{slug}',
            ],
            'features' => [
                'multi_tenant' => true,
                'b2c_portal' => true,
                'marketplace' => true,
                'integrations' => true,
            ],
            'plan' => $tenant?->only(['plan', 'status', 'billing_status']),
        ];
    }
}
