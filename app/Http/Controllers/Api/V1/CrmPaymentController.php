<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\CrmFolders;
use App\Models\CrmPayment;
use App\Services\Auth\AuthorizationService;
use App\Services\Operations\DepositService;
use App\Services\Finance\FinanceIntegrationService;
use App\Services\Integrations\WebhookDispatcher;

class CrmPaymentController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly DepositService $deposits,
        private readonly FinanceIntegrationService $finance,
        private readonly WebhookDispatcher $webhooks,
    ) {}

    private function requireAccountant(Request $request)
    {
        if (! $this->authz->canProcessPayments($request->user())) {
            return response()->json([
                'status' => false,
                'message' => 'Only users with payment permissions can perform this action',
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

    private function isApproved(CrmPayment $payment): bool
    {
        return strtolower((string) ($payment->status ?? '')) === 'approved';
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

    // Show single payment
    public function show($paymentId)
    {
        $payment = CrmPayment::find($paymentId);
        if (!$payment) {
            return response()->json([
                'status' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $payment
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

        $folder = CrmFolders::find($folderId);

        $payment = CrmPayment::create([
            'tenant_id' => $folder?->tenant_id,
            'folder_id' => (int) $folderId,
            'payment_type' => $request->input('payment_type', 'payment'),
            'booking_deposit_id' => $request->booking_deposit_id,
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

    // Update payment details (blocked if approved)
    public function update(Request $request, $paymentId)
    {

        $payment = CrmPayment::find($paymentId);
        if (!$payment) {
            return response()->json([
                'status' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        if ($this->isApproved($payment)) {
            return response()->json([
                'status' => false,
                'message' => 'Approved payment cannot be updated'
            ], 403);
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

    // Delete payment (blocked if approved)
    public function destroy(Request $request, $paymentId)
    {
        $payment = CrmPayment::find($paymentId);
        if (!$payment) {
            return response()->json([
                'status' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        if ($this->isApproved($payment)) {
            return response()->json([
                'status' => false,
                'message' => 'Approved payment cannot be deleted'
            ], 403);
        }

        $proof = (string) ($payment->proof ?? '');
        if ($proof !== '') {
            $path = public_path($proof);
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $payment->delete();

        return response()->json([
            'status' => true,
            'message' => 'Payment deleted successfully'
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

        if (strtolower((string) $request->status) === 'approved') {
            $payment = $payment->fresh();
            $this->deposits->allocatePayment($payment, $payment->booking_deposit_id);
            $this->finance->onPaymentApproved($payment);

            if ($payment->tenant_id) {
                $this->webhooks->dispatch((int) $payment->tenant_id, 'payment.approved', [
                    'id' => $payment->id,
                    'folder_id' => $payment->folder_id,
                    'amount' => $payment->payment,
                    'status' => $payment->status,
                    'payment_mode' => $payment->payment_mode,
                    'pdate' => $payment->pdate,
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Payment processed successfully',
            'data' => $payment->fresh()
        ]);
    }
}

