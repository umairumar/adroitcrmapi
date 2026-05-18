<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ScopesLeadQueries;
use App\Http\Controllers\Controller;
use App\Models\CrmLead;
use App\Models\Tenant;
use App\Services\Auth\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CrmLeadController extends Controller
{
    use ScopesLeadQueries;

    public function __construct(
        private readonly AuthorizationService $authz,
    ) {}

    // CREATE DIRECT LEAD (public; pass tenant_slug to scope lead)
    public function directstore(Request $request)
    {
            $tenantId = null;
            if ($request->filled('tenant_slug')) {
                $tenant = Tenant::where('slug', $request->tenant_slug)->where('status', 'active')->first();
                if (! $tenant) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid organization.',
                    ], 422);
                }
                $tenantId = $tenant->id;
            }

            $lead = CrmLead::withoutGlobalScopes()->create(array_merge(
                [
                    'tenant_id' => $tenantId,
                    'name'      => $request->name,
                    'email'     => $request->email,
                    'phone'     => $request->phone,
                    'lead_details' => $request->lead_details,
                    'mby'       => $request->mby,
                    'mdate'     => now(),
                    'cby'       => $request->cby,
                    'cdate'     => now(),
                ]
            ));

            return response()->json([
                'status'  => true,
                'message' => 'Lead created successfully',
            ], 201);
    }
    
    // LIST NEW LEADS
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 9);

        $crmLeads = $this->scopedLeadsQuery($request);

        if ($request->filled('search')) {
            $crmLeads->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
            if ($request->filled('date_from') && $request->filled('date_to')) {
                    $crmLeads->whereBetween('created_at', [
                        $request->date_from . ' 00:00:00',
                        $request->date_to . ' 23:59:59'
                    ]);
            }
        }
        
        $crmLeads = $crmLeads
            ->where('status', 'New')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
            
        return response()->json([
            'status' => true,
            'data'   => $crmLeads,
            'meta'   => [
                    'current_page' => $crmLeads->currentPage(),
                    'last_page'    => $crmLeads->lastPage(),
                    'per_page'     => $crmLeads->perPage(),
                    'total'        => $crmLeads->total(),
                ]
        ]);
    }

    // GET SINGLE LEAD
    public function show($id)
    {
        $lead = CrmLead::with('remarks')->find($id);

        if (!$lead) {
            return response()->json([
                'status'  => false,
                'message' => 'Lead not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $lead
        ]);
    }

    // CREATE LEAD
    public function store(Request $request)
    {
            $authUser = $request->user();

            $validator = Validator::make($request->all(), [
                'name'        => 'required',
                'email'       => 'required|email',
                'phone'       => 'required'
            ], [
                'email.email' => 'Invalid email address'
            ]);
            /*
                'departure.required'    => 'Departure is required',
                'destination.required'  => 'Destination is required',
            */
            /*
                'departure'   => 'required',
                'destination' => 'required',
                'class'       => 'required',
                'ddate'       => 'required',
                'adult'       => 'required|integer',
                'child'       => 'required|integer',
                'infant'      => 'required|integer',
                'brand'       => 'required',
                'lead_type'   => 'required',
                'status'      => 'required', 
            */
    
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            // Only allow fields that exist on the model (prevents mass-assignment surprises).
            $payload = $request->only((new CrmLead())->getFillable());

            // Default status if not provided
            $payload['status'] = $payload['status'] ?? 'New';

            // If an agent is creating a lead, auto-assign the lead to that agent.
            if ($authUser && ! $this->authz->isTenantAdmin($authUser)) {
                $payload['agent'] = $authUser->id;
            }

            // Default company from authenticated user if missing.
            if ($authUser && empty($payload['company'])) {
                $payload['company'] = $authUser->company;
            }

            // Audit fields
            $payload['cby']     = $authUser?->id ?? ($payload['cby'] ?? 0);
            $payload['mby']     = ($payload['mby'] ?? 0);
            $payload['cdate']   = $payload['cdate'] ?? now();
            $payload['mdate']   = now();

            $lead = CrmLead::create($payload);

            return response()->json([
                'status'  => true,
                'message' => 'Lead created successfully',
                'data'    => $lead,
            ], 201);
            
    }

    // UPDATE LEAD
    public function update(Request $request, $id)
    {
        $lead = CrmLead::find($id);

        if (!$lead) {
            return response()->json([
                'status'  => false,
                'message' => 'Lead not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'        => 'required',
            'email'       => 'required|email',
            'phone'       => 'required'
        ]);
        /*
            'adult'  => 'sometimes|integer',
            'child'  => 'sometimes|integer',
            'infant' => 'sometimes|integer',
        */
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Only allow fields that exist on the model (prevents mass-assignment surprises).
        $payload = $request->only((new CrmLead())->getFillable());

        // Audit fields
        $payload['mby'] = $request->mby ?? 0;
        $payload['mdate'] = now();

        $lead->update($payload);

        return response()->json([
            'status'  => true,
            'message' => 'Lead updated successfully',
            'data'    => $lead
        ]);
        
    }

    // DELETE LEAD
    public function destroy($id)
    {
        $lead = CrmLead::find($id);

        if (!$lead) {
            return response()->json([
                'status'  => false,
                'message' => 'Lead not found'
            ], 404);
        }

        $lead->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Lead deleted successfully'
        ]);
    }

    // ASSIGN Agent to LEAD
    public function leadsassign(Request $request, $id)
    {
        $lead = CrmLead::find($id);

        if (!$lead) {
            return response()->json([
                'status'  => false,
                'message' => 'Lead not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'agent'  => 'sometimes|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $lead->update(array_merge(
            [
                'agent'     => $request->agent ?? 0,
                'mby'       => $request->mby ?? 0,
                'status'    => 'Open',
                'mdate'     => now()
            ]
        ));

        return response()->json([
            'status'  => true,
            'message' => 'Lead Assigned successfully',
            'data'    => $lead
        ]);

    }

    // Opened LEADS LIST
    public function openleads(Request $request)
    {
        $perPage = $request->get('per_page', 9);

        $crmLeads = $this->scopedLeadsQuery($request);

        if ($request->filled('search')) {
            $crmLeads->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
            if ($request->filled('date_from') && $request->filled('date_to')) {
                    $crmLeads->whereBetween('created_at', [
                        $request->date_from . ' 00:00:00',
                        $request->date_to . ' 23:59:59'
                    ]);
            }
        }

        $crmLeads = $crmLeads->where('status','Open')->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'status' => true,
            'data'   => $crmLeads,
            'meta'   => [
                    'current_page' => $crmLeads->currentPage(),
                    'last_page'    => $crmLeads->lastPage(),
                    'per_page'     => $crmLeads->perPage(),
                    'total'        => $crmLeads->total(),
            ]
        ]);
    }

    // Move to Booked Leads
    public function movetobookedleads(Request $request, $id)
    {
        $lead = CrmLead::find($id);

        if (!$lead) {
            return response()->json([
                'status'  => false,
                'message' => 'Lead not found'
            ], 404);
        }

        $lead->update(array_merge(
            [
                'status'    => 'Booked',
                'mdate'     => now()
            ]
        ));

        return response()->json([
            'status'  => true,
            'message' => 'Lead Booked successfully',
            'data'    => $lead
        ]);

    }

    // Booked LEADS LIST
    public function bookedleads(Request $request)
    {
        $perPage = $request->get('per_page', 9);

        $crmLeads = $this->scopedLeadsQuery($request);

        if ($request->filled('search')) {
            $crmLeads->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
            if ($request->filled('date_from') && $request->filled('date_to')) {
                    $crmLeads->whereBetween('created_at', [
                        $request->date_from . ' 00:00:00',
                        $request->date_to . ' 23:59:59'
                    ]);
            }
        }

        $crmLeads = $crmLeads->where('status','Booked')->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'status' => true,
            'data'   => $crmLeads,
            'meta'   => [
                    'current_page' => $crmLeads->currentPage(),
                    'last_page'    => $crmLeads->lastPage(),
                    'per_page'     => $crmLeads->perPage(),
                    'total'        => $crmLeads->total(),
            ]
        ]);
    }

    // Move to Not Booked Leads
    public function movetonotbookedleads(Request $request, $id)
    {
        $lead = CrmLead::find($id);

        if (!$lead) {
            return response()->json([
                'status'  => false,
                'message' => 'Lead not found'
            ], 404);
        }

        $lead->update(array_merge(
            [
                'status'    => 'Not Booked',
                'mdate'     => now()
            ]
        ));

        return response()->json([
            'status'  => true,
            'message' => 'Lead Not Booked successfully',
            'data'    => $lead
        ]);

    }

    // Booked LEADS LIST
    public function notbookedleads(Request $request)
    {
        $perPage = $request->get('per_page', 9);

        $crmLeads = $this->scopedLeadsQuery($request);
        
        if ($request->filled('search')) {
            $crmLeads->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
            if ($request->filled('date_from') && $request->filled('date_to')) {
                    $crmLeads->whereBetween('created_at', [
                        $request->date_from . ' 00:00:00',
                        $request->date_to . ' 23:59:59'
                    ]);
            }
        }

        $crmLeads = $crmLeads->where('status','Not Booked')->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'status' => true,
            'data'   => $crmLeads,
            'meta'   => [
                    'current_page' => $crmLeads->currentPage(),
                    'last_page'    => $crmLeads->lastPage(),
                    'per_page'     => $crmLeads->perPage(),
                    'total'        => $crmLeads->total(),
            ]
        ]);
    }

    // Move to Archive Leads
    public function movetoarchiveleads(Request $request, $id)
    {
        $lead = CrmLead::find($id);

        if (!$lead) {
            return response()->json([
                'status'  => false,
                'message' => 'Lead not found'
            ], 404);
        }

        $lead->update(array_merge(
            [
                'status'    => 'Archive',
                'mdate'     => now()
            ]
        ));

        return response()->json([
            'status'  => true,
            'message' => 'Lead Archived successfully',
            'data'    => $lead
        ]);

    }

    // Archive LEADS LIST
    public function archiveleads(Request $request)
    {
        $perPage = $request->get('per_page', 9);

        $crmLeads = $this->scopedLeadsQuery($request);
        
        if ($request->filled('search')) {
            $crmLeads->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
            if ($request->filled('date_from') && $request->filled('date_to')) {
                    $crmLeads->whereBetween('created_at', [
                        $request->date_from . ' 00:00:00',
                        $request->date_to . ' 23:59:59'
                    ]);
            }
        }

        $crmLeads = $crmLeads->where('status','Archive')->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'status' => true,
            'data'   => $crmLeads,
            'meta'   => [
                    'current_page' => $crmLeads->currentPage(),
                    'last_page'    => $crmLeads->lastPage(),
                    'per_page'     => $crmLeads->perPage(),
                    'total'        => $crmLeads->total(),
            ]
        ]);
    }

    // FILTER BY COMPANY
    public function leadsByCompany($companyId)
    {
        $leads = CrmLead::where('company', $companyId)->get();

        return response()->json([
            'status' => true,
            'count'  => $leads->count(),
            'data'   => $leads
        ]);
    }

    // FILTER BY AGENT
    public function leadsByAgent($agentId)
    {
        $leads = CrmLead::where('agent', $agentId)->get();

        return response()->json([
            'status' => true,
            'count'  => $leads->count(),
            'data'   => $leads
        ]);
    }

    public function allleads(Request $request)
    {
        $perPage = $request->get('per_page', 9);
        
        $crmLeads = $this->scopedLeadsQuery($request);
        
        if ($request->filled('search')) {
            $crmLeads->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
            if ($request->filled('date_from') && $request->filled('date_to')) {
                    $crmLeads->whereBetween('created_at', [
                        $request->date_from . ' 00:00:00',
                        $request->date_to . ' 23:59:59'
                    ]);
            }
        }
        
        $crmLeads = $crmLeads
            ->orderBy('id', 'desc')
            ->paginate($perPage);
            
        return response()->json([
            'status' => true,
            'data'   => $crmLeads,
            'meta'   => [
                    'current_page' => $crmLeads->currentPage(),
                    'last_page'    => $crmLeads->lastPage(),
                    'per_page'     => $crmLeads->perPage(),
                    'total'        => $crmLeads->total(),
                ]
        ]);
    }

}
