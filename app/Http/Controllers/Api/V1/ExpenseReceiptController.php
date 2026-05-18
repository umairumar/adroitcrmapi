<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReceipt;
use App\Services\Auth\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseReceiptController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
    ) {}

    public function index(Request $request)
    {
        $q = ExpenseReceipt::orderByDesc('receipt_date');
        if ($request->filled('folder_id')) {
            $q->where('folder_id', $request->folder_id);
        }

        return response()->json(['status' => true, 'data' => $q->paginate(20)]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'receipt_date' => 'required|date',
            'category' => 'nullable|string',
            'folder_id' => 'nullable|integer',
            'file' => 'nullable|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $filePath = null;
        if ($request->hasFile('file')) {
            $dir = public_path('uploads/receipts');
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move($dir, $filename);
            $filePath = 'uploads/receipts/' . $filename;
        }

        $receipt = ExpenseReceipt::create([
            'folder_id' => $request->folder_id,
            'user_id' => $request->user()->id,
            'category' => $request->category ?? 'other',
            'amount' => $request->amount,
            'receipt_date' => $request->receipt_date,
            'file_path' => $filePath,
            'vendor_name' => $request->vendor_name,
            'notes' => $request->notes,
        ]);

        return response()->json(['status' => true, 'data' => $receipt], 201);
    }

    public function approve(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'receipts.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $receipt = ExpenseReceipt::findOrFail($id);
        $receipt->update(['status' => $request->input('status', 'approved')]);

        return response()->json(['status' => true, 'data' => $receipt]);
    }
}
