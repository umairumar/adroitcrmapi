<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\CrmFolders;
use App\Models\CrmPayment;

class CrmPaymentController extends Controller
{
    private function requireAccountant(Request $request)
    {
        $authUser = $request->user();

        $utype = (string) ($authUser->utype ?? '');
        if ($utype !== 'Accountant') {
            return response()->json([
                'status' => false,
                'message' => 'Only Accountant can perform this action'
            ], 403);
        }

        return null;
    }

    // List payments for a folder
    public function index($folderId)
    {
        $payments = CrmPayment::where('folder_id', $folderId)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $payments
        ]);
    }

    // Add payment against folder
    public function store(Request $request, $folderId)
    {
        CrmFolders::findOrFail($folderId);

        $validator = Validator::make($request->all(), [
            'payment' => 'required',
            'pdate' => 'required|date',
            'payment_mode' => 'required',
            'proof' => 'required',
            'status' => 'sometimes|string|max:50',
            'remarks' => 'sometimes|nullable|string|max:500',
            'cby' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $payment = CrmPayment::create([
            'folder_id' => (int) $folderId,
            'payment' => $request->payment,
            'pdate' => $request->pdate,
            'payment_mode' => $request->payment_mode,
            'proof' => $request->proof,
            'status' => $request->status ?? 'pending',
            'remarks' => $request->remarks,
            'cby' => (int) $request->cby,
            'cdate' => $request->cdate ?? Carbon::now(),
            // Table has NOT NULL: keep safe defaults until approved/rejected
            'process_by' => (int) ($request->process_by ?? 0),
            'process_date' => $request->process_date ?? Carbon::now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Payment added successfully',
            'data' => $payment
        ], 201);
    }

    // Update payment details (Accountant only)
    public function update(Request $request, $paymentId)
    {
        if ($resp = $this->requireAccountant($request)) {
            return $resp;
        }

        $payment = CrmPayment::find($paymentId);
        if (!$payment) {
            return response()->json([
                'status' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'payment' => 'sometimes|required',
            'pdate' => 'sometimes|required|date',
            'payment_mode' => 'sometimes|required',
            'proof' => 'sometimes|required',
            'remarks' => 'sometimes|nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $payload = $request->only(['payment', 'pdate', 'payment_mode', 'proof', 'remarks']);
        $payment->update($payload);

        return response()->json([
            'status' => true,
            'message' => 'Payment updated successfully',
            'data' => $payment
        ]);
    }

    // Approve/Reject payment
    public function process(Request $request, $paymentId)
    {
        if ($resp = $this->requireAccountant($request)) {
            return $resp;
        }

        $payment = CrmPayment::find($paymentId);
        if (!$payment) {
            return response()->json([
                'status' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|max:50',
            'process_by' => 'required|integer',
            'remarks' => 'sometimes|nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $payment->update([
            'status' => $request->status,
            'process_by' => (int) $request->process_by,
            'process_date' => Carbon::now(),
            'remarks' => $request->remarks ?? $payment->remarks,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Payment processed successfully',
            'data' => $payment
        ]);
    }
}

