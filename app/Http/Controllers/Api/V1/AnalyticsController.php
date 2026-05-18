<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthorizationService;
use App\Services\Engagement\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly AnalyticsService $analytics,
    ) {}

    private function authorizeAnalytics(Request $request): ?\Illuminate\Http\JsonResponse
    {
        if (! $this->authz->hasPermission($request->user(), 'analytics.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        return null;
    }

    public function funnel(Request $request)
    {
        if ($r = $this->authorizeAnalytics($request)) {
            return $r;
        }

        return response()->json([
            'status' => true,
            'data' => $this->analytics->funnelConversion(
                $request->user(),
                $request->date_from,
                $request->date_to,
            ),
        ]);
    }

    public function cohorts(Request $request)
    {
        if ($r = $this->authorizeAnalytics($request)) {
            return $r;
        }

        return response()->json([
            'status' => true,
            'data' => $this->analytics->cohortByMonth(
                $request->user(),
                (int) $request->input('months', 6),
            ),
        ]);
    }

    public function ltv(Request $request)
    {
        if ($r = $this->authorizeAnalytics($request)) {
            return $r;
        }

        return response()->json([
            'status' => true,
            'data' => $this->analytics->customerLtv(
                $request->user()->tenant_id,
                (int) $request->input('limit', 20),
            ),
        ]);
    }

    public function branches(Request $request)
    {
        if ($r = $this->authorizeAnalytics($request)) {
            return $r;
        }

        return response()->json([
            'status' => true,
            'data' => $this->analytics->branchComparison($request->user()),
        ]);
    }

    public function engagement(Request $request)
    {
        if ($r = $this->authorizeAnalytics($request)) {
            return $r;
        }

        return response()->json([
            'status' => true,
            'data' => $this->analytics->engagementOverview(),
        ]);
    }
}
