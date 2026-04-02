<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;


use App\Models\User;
use App\Models\CrmFolders;

class FoldersController extends Controller
{
// LIST
    public function index()
    {
        return CrmFolders::with(['itineraries', 'passengers'])->get();
    }

    // SHOW SINGLE
    public function show($id)
    {
        return CrmFolders::with(['itineraries', 'passengers'])
            ->findOrFail($id);
    }

    // ADD
    public function store(Request $request)
    {
            $data = $request->json()->all();
            
            DB::transaction(function () use ($data) {
                $folder = CrmFolders::create([
                    'order_type' => $data['order_type'] ?? null,
                    'vendor_ref' => $data['vendor_ref'] ?? null,
                    'company' => $data['company'] ?? null,
                    'booked_by' => $data['booked_by'] ?? null,
                    'invoice_status' => $data['invoice_status'] ?? null,
                    'closed_on' => $data['closed_on'] ?? null,
                    'destination' => $data['destination'] ?? null,
                    'travel_date' => $data['travel_date'] ?? null,
                    'no_of_passengers' => $data['no_of_passengers'] ?? null,
                    'sell' => $data['sell'] ?? null,
                    'cost' => $data['cost'] ?? null,
                    'commission' => $data['commission'] ?? null,
                    'remaining' => $data['remaining'] ?? null,
                    'cby' => $data['cby'] ?? null,
                    'cdate' => $data['cdate'] ?? null,
                    'mby' => $data['mby'] ?? null,
                    'mdate' => $data['mdate'] ?? null,
                ]);
            
                if (!empty($data['itineraries'])) {
                    foreach ($data['itineraries'] as $row) {
                        $folder->itineraries()->create($row);
                    }
                }
            
                if (!empty($data['passengers'])) {
                    foreach ($data['passengers'] as $row) {
                        $folder->passengers()->create($row);
                    }
                }
            });
            
            return response()->json([
                    'status'  => true,
                    'message' => 'Folder created successfully',
            ], 201);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        DB::transaction(function () use ($request, $id) {

            $folder = CrmFolders::findOrFail($id);
            $folder->update($request->all());

            // delete old and insert new
            $folder->itineraries()->delete();
            $folder->passengers()->delete();

            if ($request->itineraries) {
                foreach ($request->itineraries as $row) {
                    $folder->itineraries()->create($row);
                }
            }

            if ($request->passengers) {
                foreach ($request->passengers as $row) {
                    $folder->passengers()->create($row);
                }
            }
        });

        return response()->json(['status'=>true,'message'=>'Folder updated']);
    }

    // DELETE
    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $folder = CrmFolders::findOrFail($id);
            $folder->itineraries()->delete();
            $folder->passengers()->delete();
            $folder->delete();
        });

        return response()->json(['status'=>true,'message'=>'Folder deleted']);
    }    
}
