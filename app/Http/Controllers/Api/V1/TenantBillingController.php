<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantBillingInvoice;
use App\Services\Audit\AuditLogger;
use App\Services\Billing\TenantBillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TenantBillingController extends Controller
{
    public function __construct(
        private readonly TenantBillingService $billing,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Platform admin: list billing invoices (optionally filter by tenant_id).
     */
    public function index(Request $request)
    {
        if (! $request->user()->isPlatformAdmin()) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $query = TenantBillingInvoice::with('tenant')
            ->orderByDesc('id');

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'status' => true,
            'data' => $invoices->items(),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    /**
     * Tenant admin: view own organization's platform invoices.
     */
    public function myInvoices(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        if (! $tenantId) {
            return response()->json(['status' => false, 'message' => 'No tenant'], 404);
        }

        $invoices = TenantBillingInvoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['sent', 'paid', 'overdue'])
            ->orderByDesc('issue_date')
            ->get();

        return response()->json(['status' => true, 'data' => $invoices]);
    }

    /**
     * Platform admin: create a draft invoice for a tenant.
     */
    public function store(Request $request)
    {
        if (! $request->user()->isPlatformAdmin()) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|exists:tenants,id',
            'plan' => 'required|string',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $tenant = Tenant::findOrFail($request->tenant_id);

        $invoice = $this->billing->createInvoice(
            $tenant,
            $request->plan,
            Carbon::parse($request->period_start),
            Carbon::parse($request->period_end),
            $request->amount ? (float) $request->amount : null,
            $request->user()->id,
        );

        if ($request->filled('notes')) {
            $invoice->update(['notes' => $request->notes]);
        }

        $this->auditLogger->log('billing.invoice.created', $invoice, null, $invoice->toArray(), $request);

        return response()->json([
            'status' => true,
            'message' => 'Invoice created.',
            'data' => $invoice,
        ], 201);
    }

    public function send(Request $request, int $id)
    {
        if (! $request->user()->isPlatformAdmin()) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $invoice = TenantBillingInvoice::findOrFail($id);
        if ($invoice->status !== 'draft') {
            return response()->json(['status' => false, 'message' => 'Only draft invoices can be sent'], 422);
        }

        $invoice = $this->billing->sendInvoice($invoice);
        $this->auditLogger->log('billing.invoice.sent', $invoice, null, ['status' => 'sent'], $request);

        return response()->json([
            'status' => true,
            'message' => 'Invoice marked as sent. Deliver PDF/email via your billing process.',
            'data' => $invoice,
        ]);
    }

    public function markPaid(Request $request, int $id)
    {
        if (! $request->user()->isPlatformAdmin()) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'payment_reference' => 'nullable|string|max:255',
            'paid_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $invoice = TenantBillingInvoice::findOrFail($id);

        if ($invoice->status === 'paid') {
            return response()->json(['status' => false, 'message' => 'Invoice already paid'], 422);
        }

        $invoice = $this->billing->markInvoicePaid(
            $invoice,
            $request->payment_reference,
            $request->paid_at ? Carbon::parse($request->paid_at) : null,
        );

        $this->auditLogger->log('billing.invoice.paid', $invoice, null, [
            'payment_reference' => $request->payment_reference,
        ], $request);

        return response()->json([
            'status' => true,
            'message' => 'Invoice marked as paid. Tenant access restored for the billing period.',
            'data' => $invoice->load('tenant'),
        ]);
    }

    public function void(Request $request, int $id)
    {
        if (! $request->user()->isPlatformAdmin()) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $invoice = TenantBillingInvoice::findOrFail($id);
        $invoice->update(['status' => 'void']);
        $this->billing->syncTenantBillingStatus($invoice->tenant);

        return response()->json([
            'status' => true,
            'message' => 'Invoice voided.',
            'data' => $invoice,
        ]);
    }
}
