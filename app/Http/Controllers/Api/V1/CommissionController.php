<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CommissionEntry;
use App\Models\CommissionPayout;
use App\Models\StaffCommissionRule;
use App\Models\SupplierCommissionRule;
use App\Services\Auth\AuthorizationService;
use App\Services\Operations\CommissionCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommissionController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly CommissionCalculationService $commissions,
    ) {}

    public function entries(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'commissions.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $q = CommissionEntry::with(['folder', 'user', 'supplier'])->orderByDesc('id');

        if ($request->filled('folder_id')) {
            $q->where('folder_id', $request->folder_id);
        }
        if ($request->filled('user_id')) {
            $q->where('user_id', $request->user_id);
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $items = $q->paginate($request->input('per_page', 20));

        return response()->json([
            'status' => true,
            'data' => $items->items(),
            'meta' => [
                'total' => $items->total(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    public function approveEntry(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'commissions.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $entry = CommissionEntry::findOrFail($id);
        $entry->update(['status' => 'approved']);

        return response()->json(['status' => true, 'data' => $entry]);
    }

    public function report(Request $request)
    {
        return response()->json([
            'status' => true,
            'data' => $this->commissions->reportSummary(
                $request->user()->tenant_id,
                $request->date_from,
                $request->date_to,
            ),
        ]);
    }

    public function staffRules()
    {
        return response()->json([
            'status' => true,
            'data' => StaffCommissionRule::orderBy('name')->get(),
        ]);
    }

    public function storeStaffRule(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'commissions.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $rule = StaffCommissionRule::create($request->only([
            'name', 'user_id', 'applies_to', 'calculation_type', 'calculation_base',
            'rate', 'min_amount', 'max_amount', 'is_active',
        ]));

        return response()->json(['status' => true, 'data' => $rule], 201);
    }

    public function supplierRules()
    {
        return response()->json([
            'status' => true,
            'data' => SupplierCommissionRule::with('supplier')->orderBy('id')->get(),
        ]);
    }

    public function storeSupplierRule(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'commissions.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $rule = SupplierCommissionRule::create($request->only([
            'supplier_id', 'supplier_name_match', 'component', 'calculation_type',
            'calculation_base', 'rate', 'is_active',
        ]));

        return response()->json(['status' => true, 'data' => $rule], 201);
    }

    public function createPayout(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'commissions.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'recipient_type' => 'required|in:staff,supplier',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'user_id' => 'required_if:recipient_type,staff|integer',
            'supplier_id' => 'required_if:recipient_type,supplier|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $payout = $this->commissions->createPayout(
            $request->recipient_type,
            $request->period_start,
            $request->period_end,
            $request->user_id,
            $request->supplier_id,
            $request->user()->id,
        );

        return response()->json(['status' => true, 'data' => $payout], 201);
    }

    public function payouts(Request $request)
    {
        $items = CommissionPayout::orderByDesc('id')->paginate(20);

        return response()->json(['status' => true, 'data' => $items->items(), 'meta' => ['total' => $items->total()]]);
    }

    public function markPayoutPaid(Request $request, int $id)
    {
        $payout = CommissionPayout::findOrFail($id);
        $payout->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $request->payment_reference,
        ]);

        CommissionEntry::where('payout_id', $payout->id)->update(['status' => 'paid']);

        return response()->json(['status' => true, 'data' => $payout]);
    }
}
