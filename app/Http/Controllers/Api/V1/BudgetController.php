<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Services\Auth\AuthorizationService;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
    ) {}

    public function index(Request $request)
    {
        $q = Budget::query()->orderByDesc('fiscal_year');
        if ($request->filled('year')) {
            $q->where('fiscal_year', $request->year);
        }

        return response()->json(['status' => true, 'data' => $q->get()]);
    }

    public function store(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'finance.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $budget = Budget::create($request->only([
            'name', 'branch_id', 'account_id', 'fiscal_year', 'period_month', 'amount',
        ]));

        return response()->json(['status' => true, 'data' => $budget], 201);
    }
}
