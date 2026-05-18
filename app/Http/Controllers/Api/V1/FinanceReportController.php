<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthorizationService;
use App\Services\Finance\AccountsPayableService;
use App\Services\Finance\AccountsReceivableService;
use App\Services\Finance\BudgetService;
use App\Services\Finance\GeneralLedgerService;
use Illuminate\Http\Request;

class FinanceReportController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly GeneralLedgerService $gl,
        private readonly AccountsReceivableService $ar,
        private readonly AccountsPayableService $ap,
        private readonly BudgetService $budgets,
    ) {}

    private function authorizeFinance(Request $request): ?\Illuminate\Http\JsonResponse
    {
        if (! $this->authz->hasPermission($request->user(), 'finance.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        return null;
    }

    public function trialBalance(Request $request)
    {
        if ($r = $this->authorizeFinance($request)) {
            return $r;
        }

        return response()->json([
            'status' => true,
            'data' => $this->gl->trialBalance($request->date_from, $request->date_to),
        ]);
    }

    public function arAging(Request $request)
    {
        if ($r = $this->authorizeFinance($request)) {
            return $r;
        }

        return response()->json(['status' => true, 'data' => $this->ar->agingReport()]);
    }

    public function apAging(Request $request)
    {
        if ($r = $this->authorizeFinance($request)) {
            return $r;
        }

        return response()->json(['status' => true, 'data' => $this->ap->agingReport()]);
    }

    public function budgetVariance(Request $request)
    {
        if ($r = $this->authorizeFinance($request)) {
            return $r;
        }

        return response()->json([
            'status' => true,
            'data' => $this->budgets->varianceReport(
                (int) $request->input('year', now()->year),
                $request->month ? (int) $request->month : null,
            ),
        ]);
    }
}
