<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SupplierBill;
use App\Services\Auth\AuthorizationService;
use App\Services\Finance\AccountsPayableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierBillController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly AccountsPayableService $ap,
    ) {}

    public function index()
    {
        return response()->json([
            'status' => true,
            'data' => SupplierBill::with('lines')->orderByDesc('id')->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'finance.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'supplier_id' => 'nullable|integer',
            'folder_id' => 'nullable|integer',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $bill = $this->ap->createBill($request->all(), $request->user()->id);

        return response()->json(['status' => true, 'data' => $bill], 201);
    }

    public function pay(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'finance.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate(['amount' => 'required|numeric|min:0.01']);
        $bill = SupplierBill::findOrFail($id);
        $this->ap->recordPayment($bill, (float) $request->amount, $request->payment_reference);

        return response()->json(['status' => true, 'data' => $bill->fresh()]);
    }
}
