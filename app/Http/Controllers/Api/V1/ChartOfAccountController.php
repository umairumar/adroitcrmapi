<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Services\Auth\AuthorizationService;
use App\Services\Finance\ChartOfAccountsService;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly ChartOfAccountsService $coa,
    ) {}

    public function index()
    {
        return response()->json([
            'status' => true,
            'data' => ChartOfAccount::orderBy('code')->get(),
        ]);
    }

    public function seed(Request $request)
    {
        if (! $request->user()->isPlatformAdmin() && ! $this->authz->hasPermission($request->user(), 'finance.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $tenantId = $request->user()->tenant_id;
        if ($tenantId) {
            $this->coa->seedForTenant($tenantId);
        }

        return response()->json(['status' => true, 'message' => 'Chart of accounts seeded']);
    }

    public function store(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'finance.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $account = ChartOfAccount::create($request->only(['code', 'name', 'type', 'is_active']));

        return response()->json(['status' => true, 'data' => $account], 201);
    }
}
