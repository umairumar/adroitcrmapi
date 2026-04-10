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

    private function requireSavedFolder(int $folderId)
    {
        if ($folderId <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'Folder must be saved before adding payment'
            ], 422);
        }

        $folder = CrmFolders::find($folderId);
        if (!$folder) {
            return response()->json([
                'status' => false,
                'message' => 'Folder must be saved before adding payment'
            ], 422);
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
        $authUser = $request->user();

        if ($resp = $this->requireSavedFolder((int) $folderId)) {
            return $resp;
        }

        $validator = Validator::make($request->all(), [
            'payment' => 'required',
            'pdate' => 'required|date',
            'payment_mode' => 'required',
            // Accept actual upload; store path in DB.
            'proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $dir = public_path('uploads/proofs');
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            $file = $request->file('proof');
            $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move($dir, $filename);
            $proofPath = 'uploads/proofs/' . $filename;
        }

        $payment = CrmPayment::create([
            'folder_id' => (int) $folderId,
            'payment' => $request->payment,
            'pdate' => $request->pdate,
            'payment_mode' => $request->payment_mode,
            'proof' => (string) $proofPath,
            // New payments are always pending; only Accountant can approve/reject.
            'status' => 'pending',
            'remarks' => $request->remarks,
            'cby' => (int) ($authUser->id ?? 0),
            'cdate' => Carbon::now(),
            // Table has NOT NULL: keep safe defaults until approved/rejected
            'process_by' => 0,
            'process_date' => Carbon::now(),
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
            'proof' => 'sometimes|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $payload = $request->only(['payment', 'pdate', 'payment_mode', 'remarks']);

        if ($request->hasFile('proof')) {
            $dir = public_path('uploads/proofs');
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            $file = $request->file('proof');
            $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move($dir, $filename);
            $payload['proof'] = 'uploads/proofs/' . $filename;
        }

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

        $authUser = $request->user();

        $payment = CrmPayment::find($paymentId);
        if (!$payment) {
            return response()->json([
                'status' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $payment->update([
            'status' => $request->status,
            'process_by' => (int) ($authUser->id ?? 0),
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

