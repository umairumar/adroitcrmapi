<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CrmFolders;
use App\Models\CrmLead;
use App\Models\CrmPayment;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use App\Services\Sales\PipelineService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly PipelineService $pipeline,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => true,
            'data'   => [
                'leads'    => $this->leadStats($user),
                'folders'  => $this->folderStats($user),
                'payments' => $this->paymentStats($user),
                'trend'    => $this->monthlyLeadTrend($user),
                'agents'   => $this->agentLeaderboard($user),
                'recent'   => $this->recentLeads($user),
                'pipeline' => $this->authz->hasPermission($user, 'pipeline.view')
                    ? [
                        'funnel' => $this->pipeline->funnelStats($user),
                        'sla_breaches_count' => $this->pipeline->slaBreaches($user)->count(),
                    ]
                    : null,
            ],
        ]);
    }

    private function leadStats(User $user): array
    {
        $base = fn () => $this->authz->scopeLeads(CrmLead::query(), $user);

        $byStatus = (clone $base())
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $today = Carbon::today();

        return [
            'total'       => array_sum($byStatus),
            'by_status'   => [
                'new'        => (int) ($byStatus['New']        ?? 0),
                'open'       => (int) ($byStatus['Open']       ?? 0),
                'booked'     => (int) ($byStatus['Booked']     ?? 0),
                'not_booked' => (int) ($byStatus['Not Booked'] ?? 0),
                'archive'    => (int) ($byStatus['Archive']    ?? 0),
            ],
            'today'            => (clone $base())->whereDate('cdate', $today)->count(),
            'this_week'        => (clone $base())->whereBetween('cdate', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count(),
            'this_month'       => (clone $base())->whereMonth('cdate', $today->month)->whereYear('cdate', $today->year)->count(),
            'conversion_rate'  => $this->conversionRate($byStatus),
        ];
    }

    private function conversionRate(array $byStatus): float
    {
        $booked    = (int) ($byStatus['Booked']     ?? 0);
        $notBooked = (int) ($byStatus['Not Booked'] ?? 0);
        $denom     = $booked + $notBooked;

        return $denom > 0 ? round(($booked / $denom) * 100, 1) : 0.0;
    }

    private function folderStats(User $user): array
    {
        $q = $this->authz->scopeFolders(CrmFolders::query(), $user);

        $agg = (clone $q)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('COALESCE(SUM(sell), 0) as total_sell'),
                DB::raw('COALESCE(SUM(cost), 0) as total_cost'),
                DB::raw('COALESCE(SUM(commission), 0) as total_commission'),
                DB::raw('COALESCE(SUM(remaining), 0) as total_remaining')
            )
            ->first();

        $byInvoice = (clone $q)
            ->select('invoice_status', DB::raw('COUNT(*) as count'))
            ->groupBy('invoice_status')
            ->pluck('count', 'invoice_status')
            ->toArray();

        return [
            'total'             => (int)   ($agg->total            ?? 0),
            'total_sell'        => (float) ($agg->total_sell       ?? 0),
            'total_cost'        => (float) ($agg->total_cost       ?? 0),
            'total_commission'  => (float) ($agg->total_commission ?? 0),
            'total_remaining'   => (float) ($agg->total_remaining  ?? 0),
            'by_invoice_status' => $byInvoice,
        ];
    }

    private function paymentStats(User $user): array
    {
        $folderIds = $this->authz->scopeFolders(CrmFolders::query(), $user)->pluck('id');

        $q = CrmPayment::whereIn('folder_id', $folderIds);

        $agg = (clone $q)
            ->select(
                DB::raw('COALESCE(SUM(payment), 0) as total_amount'),
                DB::raw('COALESCE(SUM(CASE WHEN status = "approved" THEN payment ELSE 0 END), 0) as approved_amount'),
                DB::raw('COALESCE(SUM(CASE WHEN status = "pending"  THEN payment ELSE 0 END), 0) as pending_amount'),
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count'),
                DB::raw('SUM(CASE WHEN status = "pending"  THEN 1 ELSE 0 END) as pending_count')
            )
            ->first();

        return [
            'total_amount'    => (float) ($agg->total_amount    ?? 0),
            'approved_amount' => (float) ($agg->approved_amount ?? 0),
            'pending_amount'  => (float) ($agg->pending_amount  ?? 0),
            'total_count'     => (int)   ($agg->total_count     ?? 0),
            'approved_count'  => (int)   ($agg->approved_count  ?? 0),
            'pending_count'   => (int)   ($agg->pending_count   ?? 0),
        ];
    }

    private function monthlyLeadTrend(User $user): array
    {
        $months = collect(range(5, 0))->map(fn ($i) => Carbon::now()->subMonths($i));

        return $months->map(function (Carbon $month) use ($user) {
            $count = $this->authz->scopeLeads(CrmLead::query(), $user)
                ->whereYear('cdate', $month->year)
                ->whereMonth('cdate', $month->month)
                ->count();

            return [
                'month'  => $month->format('Y-m'),
                'label'  => $month->format('M Y'),
                'count'  => $count,
            ];
        })->values()->toArray();
    }

    private function agentLeaderboard(User $user): array
    {
        if (! $this->authz->isTenantAdmin($user)) {
            return [];
        }

        $q = $this->authz->scopeLeads(CrmLead::query(), $user)
            ->select(
                'agent',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "Booked" THEN 1 ELSE 0 END) as booked')
            )
            ->whereNotNull('agent')
            ->where('agent', '>', 0)
            ->groupBy('agent')
            ->orderByDesc('booked')
            ->limit(10)
            ->get();

        $agentIds = $q->pluck('agent')->unique();
        $users    = User::whereIn('id', $agentIds)->get()->keyBy('id');

        return $q->map(function ($row) use ($users) {
            $u = $users->get($row->agent);

            return [
                'agent_id'   => $row->agent,
                'name'       => $u?->name  ?? 'Unknown',
                'email'      => $u?->email ?? '',
                'total'      => (int) $row->total,
                'booked'     => (int) $row->booked,
                'conversion' => $row->total > 0
                    ? round(($row->booked / $row->total) * 100, 1)
                    : 0.0,
            ];
        })->values()->toArray();
    }

    private function recentLeads(User $user): array
    {
        return $this->authz->scopeLeads(CrmLead::query(), $user)
            ->select('id', 'name', 'email', 'phone', 'status', 'lead_type', 'agent', 'cdate')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
