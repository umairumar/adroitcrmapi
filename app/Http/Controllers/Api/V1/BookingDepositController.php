<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BookingDeposit;
use App\Models\CrmFolders;
use App\Services\Auth\AuthorizationService;
use App\Services\Operations\DepositService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingDepositController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly DepositService $deposits,
    ) {}

    public function index(int $folderId)
    {
        return response()->json([
            'status' => true,
            'data' => BookingDeposit::where('folder_id', $folderId)->orderBy('due_date')->get(),
        ]);
    }

    public function store(Request $request, int $folderId)
    {
        if (! $this->authz->hasPermission($request->user(), 'deposits.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'schedule' => 'required_without:amount|array',
            'schedule.*.amount' => 'required|numeric|min:0',
            'schedule.*.due_date' => 'nullable|date',
            'schedule.*.deposit_type' => 'nullable|in:deposit,installment,balance',
            'amount' => 'required_without:schedule|numeric|min:0',
            'due_date' => 'nullable|date',
            'label' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $folder = CrmFolders::findOrFail($folderId);

        if ($request->has('schedule')) {
            $created = $this->deposits->createDepositSchedule($folder, $request->schedule);
        } else {
            $created = collect([BookingDeposit::create([
                'tenant_id' => $folder->tenant_id,
                'folder_id' => $folder->id,
                'label' => $request->label ?? 'Deposit',
                'deposit_type' => $request->deposit_type ?? 'deposit',
                'amount' => $request->amount,
                'due_date' => $request->due_date,
                'status' => 'pending',
            ])]);
            $this->deposits->refreshFolderDepositTotals($folder);
        }

        return response()->json(['status' => true, 'data' => $created], 201);
    }
}
