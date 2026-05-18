<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CrmFolders;
use App\Models\CrmLead;
use App\Services\Sales\LeadCaptureService;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\Request;

/**
 * Marketplace / external API endpoints (authenticated via X-Api-Key).
 */
class ExternalApiController extends Controller
{
    public function __construct(
        private readonly LeadCaptureService $leadCapture,
    ) {}

    public function listLeads(Request $request)
    {
        $leads = CrmLead::query()
            ->orderByDesc('id')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json(['status' => true, 'data' => $leads]);
    }

    public function createLead(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'source' => 'nullable|string',
        ]);

        $tenantId = (int) TenantContext::id();
        if (! $tenantId) {
            return response()->json(['status' => false, 'message' => 'Tenant context missing'], 500);
        }

        $lead = CrmLead::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'status' => 'New',
            'cdate' => now(),
        ]);

        $this->leadCapture->enrichNewLead($lead, $request);

        return response()->json(['status' => true, 'data' => $lead->fresh()], 201);
    }

    public function showFolder(Request $request, int $id)
    {
        $folder = CrmFolders::findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $folder->only([
                'id', 'destination', 'travel_date', 'sell', 'remaining',
                'booking_status', 'company',
            ]),
        ]);
    }
}
