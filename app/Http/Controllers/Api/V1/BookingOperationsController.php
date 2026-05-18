<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CrmFolders;
use App\Services\Auth\AuthorizationService;
use App\Services\Operations\BookingOperationsService;
use App\Services\Operations\CommissionCalculationService;
use App\Services\Operations\DepositService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingOperationsController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly BookingOperationsService $operations,
        private readonly CommissionCalculationService $commissions,
        private readonly DepositService $deposits,
    ) {}

    public function summary(Request $request, int $folderId)
    {
        $folder = CrmFolders::findOrFail($folderId);
        $this->authz->assertFolderAccessible($request->user(), $folder);

        return response()->json([
            'status' => true,
            'data' => $this->operations->operationsSummary($folder),
        ]);
    }

    public function updateStatus(Request $request, int $folderId)
    {
        if (! $this->authz->hasPermission($request->user(), 'folders.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'booking_status' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $folder = CrmFolders::findOrFail($folderId);
        $folder = $this->operations->updateStatus($folder, $request->booking_status);

        return response()->json(['status' => true, 'data' => $folder]);
    }

    public function linkLead(Request $request, int $folderId)
    {
        $validator = Validator::make($request->all(), ['lead_id' => 'required|integer']);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $folder = $this->operations->linkLead(CrmFolders::findOrFail($folderId), (int) $request->lead_id);

        return response()->json(['status' => true, 'data' => $folder]);
    }

    public function calculateCommissions(Request $request, int $folderId)
    {
        if (! $this->authz->hasPermission($request->user(), 'commissions.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $folder = CrmFolders::findOrFail($folderId);
        $entries = $this->commissions->calculateForFolder($folder);

        return response()->json([
            'status' => true,
            'message' => 'Commissions calculated',
            'data' => $entries,
        ]);
    }

    public function syncDeposits(Request $request, int $folderId)
    {
        $folder = CrmFolders::findOrFail($folderId);
        $deposits = $this->deposits->syncFromLegacyInstallments($folder);

        return response()->json(['status' => true, 'data' => $deposits]);
    }

    public function bookingStatuses()
    {
        return response()->json([
            'status' => true,
            'data' => config('operations.booking_statuses', []),
        ]);
    }
}
