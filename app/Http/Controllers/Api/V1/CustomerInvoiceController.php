<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomerInvoice;
use App\Models\CrmFolders;
use App\Services\Auth\AuthorizationService;
use App\Services\Finance\AccountsReceivableService;
use App\Services\Finance\CustomerInvoicePdfService;
use Illuminate\Http\Request;

class CustomerInvoiceController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly AccountsReceivableService $ar,
        private readonly CustomerInvoicePdfService $pdf,
    ) {}

    public function index(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'finance.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $q = CustomerInvoice::with(['lines', 'folder.passengersNames'])->orderByDesc('id');
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('folder_id')) {
            $q->where('folder_id', $request->folder_id);
        }

        return response()->json(['status' => true, 'data' => $q->paginate(20)]);
    }

    public function show(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'finance.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $invoice = CustomerInvoice::with(['lines', 'folder.passengersNames', 'folder.hotels', 'folder.lead'])
            ->findOrFail($id);

        return response()->json(['status' => true, 'data' => $invoice]);
    }

    public function preview(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'finance.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $invoice = CustomerInvoice::with(['lines', 'folder.passengersNames', 'folder.hotels', 'folder.lead', 'folder.payments'])
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $this->pdf->buildViewData($invoice),
        ]);
    }

    public function pdf(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'finance.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $invoice = CustomerInvoice::findOrFail($id);
        $disposition = $request->query('inline') ? 'stream' : 'download';

        return $disposition === 'stream'
            ? $this->pdf->stream($invoice)
            : $this->pdf->download($invoice);
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
