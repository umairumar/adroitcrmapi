<?php

namespace App\Services\Engagement;

use App\Models\Contact;
use App\Models\CrmFolders;
use App\Models\CrmLead;
use App\Models\CustomerInvoice;
use App\Services\Auth\AuthorizationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function __construct(
        private readonly AuthorizationService $authz,
    ) {}

    public function funnelConversion($user, ?string $from = null, ?string $to = null): array
    {
        $from = $from ? Carbon::parse($from) : Carbon::now()->subMonths(3);
        $to = $to ? Carbon::parse($to) : Carbon::now();

        $base = $this->authz->scopeLeads(CrmLead::query(), $user)
            ->whereBetween('cdate', [$from, $to]);

        $total = (clone $base)->count();
        $booked = (clone $base)->where('status', 'Booked')->count();

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'total_leads' => $total,
            'booked' => $booked,
            'conversion_rate' => $total > 0 ? round(($booked / $total) * 100, 1) : 0,
            'by_source' => (clone $base)
                ->select('source', DB::raw('COUNT(*) as count'))
                ->whereNotNull('source')
                ->groupBy('source')
                ->pluck('count', 'source'),
        ];
    }

    public function cohortByMonth($user, int $months = 6): array
    {
        $cohorts = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $leads = $this->authz->scopeLeads(CrmLead::query(), $user)
                ->whereBetween('cdate', [$start, $end]);

            $cohorts[] = [
                'month' => $month->format('Y-m'),
                'label' => $month->format('M Y'),
                'leads' => (clone $leads)->count(),
                'booked' => (clone $leads)->where('status', 'Booked')->count(),
            ];
        }

        return $cohorts;
    }

    public function customerLtv(?int $tenantId, int $limit = 20): array
    {
        return Contact::query()
            ->select('contacts.*')
            ->selectSub(
                CustomerInvoice::selectRaw('COALESCE(SUM(total), 0)')
                    ->whereColumn('contact_id', 'contacts.id')
                    ->where('status', 'paid'),
                'lifetime_value'
            )
            ->orderByDesc('lifetime_value')
            ->limit($limit)
            ->get()
            ->map(fn ($c) => [
                'contact_id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'loyalty_points' => $c->loyalty_points,
                'lifetime_value' => (float) ($c->lifetime_value ?? 0),
            ])
            ->all();
    }

    public function branchComparison($user): array
    {
        $folders = $this->authz->scopeFolders(CrmFolders::query(), $user)
            ->select('company', DB::raw('COUNT(*) as bookings'), DB::raw('COALESCE(SUM(sell),0) as revenue'))
            ->groupBy('company')
            ->get();

        return $folders->map(fn ($row) => [
            'company' => $row->company,
            'bookings' => (int) $row->bookings,
            'revenue' => (float) $row->revenue,
        ])->all();
    }

    public function engagementOverview(): array
    {
        return [
            'open_threads' => DB::table('conversation_threads')->where('status', 'open')->count(),
            'messages_today' => DB::table('messages')->whereDate('created_at', today())->count(),
            'active_campaigns' => DB::table('campaigns')->where('status', 'running')->count(),
            'avg_feedback_rating' => round((float) DB::table('client_feedback')->avg('rating'), 1),
        ];
    }
}
