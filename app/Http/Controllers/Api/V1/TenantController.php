<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\Auth\AuthorizationService;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
    ) {}

    /**
     * Current tenant context for the authenticated user.
     */
    public function me(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant && $request->user()?->tenant_id) {
            $tenant = Tenant::find($request->user()->tenant_id);
        }

        if (! $tenant) {
            return response()->json([
                'status' => false,
                'message' => 'No tenant context.',
            ], 404);
        }

        $limits = $tenant->planLimits();

        return response()->json([
            'status' => true,
            'data' => [
                'tenant' => $tenant,
                'plan_limits' => $limits,
                'on_trial' => $tenant->isOnTrial(),
                'permissions' => $this->permissionsForUser($request->user()),
            ],
        ]);
    }

    /**
     * Platform admin: list all tenants.
     */
    public function index(Request $request)
    {
        if (! $request->user()->isPlatformAdmin()) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $tenants = Tenant::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'status' => true,
            'data' => $tenants->items(),
            'meta' => [
                'current_page' => $tenants->currentPage(),
                'last_page' => $tenants->lastPage(),
                'per_page' => $tenants->perPage(),
                'total' => $tenants->total(),
            ],
        ]);
    }

    private function permissionsForUser($user): array
    {
        if (! $user || $user->isPlatformAdmin()) {
            return ['*'];
        }

        return \Illuminate\Support\Facades\DB::table('user_role')
            ->join('role_permission', 'role_permission.role_id', '=', 'user_role.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('user_role.user_id', $user->id)
            ->pluck('permissions.slug')
            ->unique()
            ->values()
            ->all();
    }
}
