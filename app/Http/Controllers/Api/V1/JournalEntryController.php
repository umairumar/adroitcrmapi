<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Services\Auth\AuthorizationService;
use App\Services\Finance\GeneralLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JournalEntryController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly GeneralLedgerService $gl,
    ) {}

    public function index(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'finance.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $entries = JournalEntry::with('lines.account')
            ->orderByDesc('entry_date')
            ->paginate(20);

        return response()->json(['status' => true, 'data' => $entries]);
    }

    public function show(int $id)
    {
        $entry = JournalEntry::with('lines.account')->findOrFail($id);

        return response()->json(['status' => true, 'data' => $entry]);
    }

    public function store(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'finance.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'required|string',
            'entry_date' => 'nullable|date',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required_without:lines.*.account_role|integer',
            'lines.*.account_role' => 'nullable|string',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $tenantId = $request->user()->tenant_id ?? \App\Services\Tenant\TenantContext::id();
        if (! $tenantId) {
            return response()->json(['status' => false, 'message' => 'No tenant context'], 422);
        }

        $entry = $this->gl->post(
            $tenantId,
            $request->description,
            $request->lines,
            'manual',
            null,
            $request->user()->id,
            $request->entry_date ? \Carbon\Carbon::parse($request->entry_date) : null,
        );

        return response()->json(['status' => true, 'data' => $entry], 201);
    }
}
