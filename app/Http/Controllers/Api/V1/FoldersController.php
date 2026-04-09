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
        return CrmFolders::with(['itineraries', 'passengers', 'passengersNames', 'hotels', 'transport', 'others', 'payments'])->get();
    }

    // SHOW SINGLE
    public function show($id)
    {
        return CrmFolders::with(['itineraries', 'passengers', 'passengersNames', 'hotels', 'transport', 'others', 'payments'])
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

                // crm_passengers_name (names list)
                $passengerNames = $data['passenger_names'] ?? ($data['passengers_names'] ?? ($data['passengers_name'] ?? null));
                if (!empty($passengerNames)) {
                    foreach ($passengerNames as $row) {
                        $folder->passengersNames()->create($row);
                    }
                }

                if (!empty($data['hotels'])) {
                    foreach ($data['hotels'] as $row) {
                        $folder->hotels()->create($row);
                    }
                }

                if (!empty($data['transport'])) {
                    foreach ($data['transport'] as $row) {
                        $folder->transport()->create($row);
                    }
                }

                if (!empty($data['others'])) {
                    foreach ($data['others'] as $row) {
                        $folder->others()->create($row);
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
        $updatedFolder = DB::transaction(function () use ($request, $id) {
            $data = $request->json()->all();
            if (empty($data)) {
                $data = $request->all();
            }

            $folder = CrmFolders::findOrFail($id);
            $folder->update(array_intersect_key($data, array_flip((new CrmFolders())->getFillable())));

            // Replace child lists only if provided in request
            if (array_key_exists('itineraries', $data)) {
                $folder->itineraries()->delete();
                foreach (($data['itineraries'] ?? []) as $row) {
                    $folder->itineraries()->create($row);
                }
            }

            if (array_key_exists('passengers', $data)) {
                $folder->passengers()->delete();
                foreach (($data['passengers'] ?? []) as $row) {
                    $folder->passengers()->create($row);
                }
            }

            $passengerNamesKeyProvided = array_key_exists('passenger_names', $data) || array_key_exists('passengers_names', $data) || array_key_exists('passengers_name', $data);
            if ($passengerNamesKeyProvided) {
                $folder->passengersNames()->delete();
                $passengerNames = $data['passenger_names'] ?? ($data['passengers_names'] ?? ($data['passengers_name'] ?? []));
                foreach (($passengerNames ?? []) as $row) {
                    $folder->passengersNames()->create($row);
                }
            }

            if (array_key_exists('hotels', $data)) {
                $folder->hotels()->delete();
                foreach (($data['hotels'] ?? []) as $row) {
                    $folder->hotels()->create($row);
                }
            }

            if (array_key_exists('transport', $data)) {
                $folder->transport()->delete();
                foreach (($data['transport'] ?? []) as $row) {
                    $folder->transport()->create($row);
                }
            }

            if (array_key_exists('others', $data)) {
                $folder->others()->delete();
                foreach (($data['others'] ?? []) as $row) {
                    $folder->others()->create($row);
                }
            }

            return CrmFolders::with(['itineraries', 'passengers', 'passengersNames', 'hotels', 'transport', 'others', 'payments'])
                ->findOrFail($id);
        });

        return response()->json(['status' => true, 'message' => 'Folder updated', 'data' => $updatedFolder]);
    }

    // DELETE
    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $folder = CrmFolders::findOrFail($id);
            $folder->itineraries()->delete();
            $folder->passengers()->delete();
            $folder->passengersNames()->delete();
            $folder->hotels()->delete();
            $folder->transport()->delete();
            $folder->others()->delete();
            $folder->delete();
        });

        return response()->json(['status'=>true,'message'=>'Folder deleted']);
    }    
}
