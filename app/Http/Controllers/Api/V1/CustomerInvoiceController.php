<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomerInvoice;
use App\Models\CrmFolders;
use App\Services\Auth\AuthorizationService;
use App\Services\Finance\AccountsReceivableService;
use Illuminate\Http\Request;

class CustomerInvoiceController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly AccountsReceivableService $ar,
    ) {}

    public function index(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'finance.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $q = CustomerInvoice::with('lines')->orderByDesc('id');
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        return response()->json(['status' => true, 'data' => $q->paginate(20)]);
    }

    public function show(int $id)
    {
        $invoice = CustomerInvoice::with('lines')->findOrFail($id);

        return response()->json(['status' => true, 'data' => $invoice]);
    }

    public function createFromFolder(Request $request, int $folderId)
    {
        if (! $this->authz->hasPermission($request->user(), 'finance.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $folder = CrmFolders::findOrFail($folderId);
        $this->authz->assertFolderAccessible($request->user(), $folder);

        $invoice = $this->ar->createFromFolder($folder, $request->user()->id);

        return response()->json(['status' => true, 'data' => $invoice], 201);
    }

    public function allocate(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'finance.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate(['amount' => 'required|numeric|min:0.01']);
        $invoice = CustomerInvoice::findOrFail($id);
        $this->ar->allocatePayment($invoice, (float) $request->amount);

        return response()->json(['status' => true, 'data' => $invoice->fresh()]);
    }
}
