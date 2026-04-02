<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeadRemark;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class LeadRemarkController extends Controller
{
    // List leads remarks by lead
    public function index($leadId)
    {
        $remarks = LeadRemark::where('lead_id', $leadId)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $remarks
        ]);
    }

    // GET SINGLE REMARKS FOR LEADS REMARKS ID
    public function show($id)
    {
        $remarks = LeadRemark::find($id);

        if (!$remarks) {
            return response()->json([
                'status'  => false,
                'message' => 'Lead Remarks not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $remarks
        ]);
    }

    // Add Leads Remarks
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|integer',
            'remarks' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $remark = LeadRemark::create([
            'lead_id' => $request->lead_id,
            'remarks' => $request->remarks,
            'cby'     => $request->cby,//auth()->id()
            'cdate'   => Carbon::now()
        ]);

        return response()->json([
            'status' => true,                                                                                                                                                                                                                                                                                                                                                                                                                                      
            'message' => 'Remark added successfully',
            'data' => $remark
        ]);
    }

    // Update remark
    public function update(Request $request, $id)
    {
        $remark = LeadRemark::find($id);

        if (!$remark) {
            return response()->json([
                'status' => false,
                'message' => 'Remark not found'
            ], 404);
        }

        $request->validate([
            'remarks' => 'required|string|max:5000'
        ]);

        $remark->update([
            'remarks' => $request->remarks,
            'cdate'   => Carbon::now()
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Remark updated successfully',
            'data' => $remark
        ]);
    }

    // Delete remark
    public function destroy($id)
    {
        $remark = LeadRemark::find($id);

        if (!$remark) {
            return response()->json([
                'status' => false,
                'message' => 'Remark not found'
            ], 404);
        }

        $remark->delete();

        return response()->json([
            'status' => true,
            'message' => 'Remark deleted successfully'
        ]);
    }
}
