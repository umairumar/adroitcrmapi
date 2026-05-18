<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use App\Services\Auth\AuthorizationService;
use Illuminate\Http\Request;

class TaxRateController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
    ) {}

    public function index()
    {
        return response()->json(['status' => true, 'data' => TaxRate::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'finance.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $rate = TaxRate::create($request->only(['name', 'code', 'rate', 'is_default', 'is_active']));

        return response()->json(['status' => true, 'data' => $rate], 201);
    }
}
