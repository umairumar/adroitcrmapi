<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;


use App\Models\User;
use App\Models\CrmFolders;
use App\Services\UmrahPackagePdfParser;

class FoldersController extends Controller
{
    private function normalizeInstallments(array $data): array
    {
        $installments = $data['installments']
            ?? $data['folder_installments']
            ?? $data['installments_plan']
            ?? [];

        return is_array($installments) ? $installments : [];
    }

    private function mapInstallmentRow(array $row, int $folderId): array
    {
        $amount = isset($row['installment_amount']) ? (float) $row['installment_amount'] : 0.00;
        $due = array_key_exists('installment_due', $row) ? (float) $row['installment_due'] : $amount;
        $status = array_key_exists('installment_payment_status', $row) ? (int) $row['installment_payment_status'] : 0;

        return [
            'folder_id' => $folderId,
            'installment_payment_date' => $row['installment_payment_date'] ?? null,
            'installment_amount' => $amount,
            'installment_due' => $due,
            'installment_payment_status' => $status,
        ];
    }

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
            if (empty($data)) {
                $data = $request->all();
            }
            
            $folder = DB::transaction(function () use ($data) {
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

                $installments = $this->normalizeInstallments($data);
                if (!empty($installments)) {
                    foreach ($installments as $row) {
                        $mapped = $this->mapInstallmentRow(is_array($row) ? $row : [], (int) $folder->id);
                        if (empty($mapped['installment_payment_date'])) {
                            continue;
                        }
                        DB::table('crm_folders_installments')->insert($mapped);
                    }
                }
                
                return $folder;
            });
            
            return response()->json([
                    'status'  => true,
                    'message' => 'Folder created successfully',
                    'folder_id' => $folder->id ?? null,
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

    // UPDATE INSTALLMENTS ONLY (do not update folder info)
    public function updateInstallments(Request $request, $folderId)
    {
        $data = $request->json()->all();
        if (empty($data)) {
            $data = $request->all();
        }

        $installments = $this->normalizeInstallments($data);

        $validator = Validator::make(
            ['installments' => $installments],
            [
                'installments' => 'required|array',
                'installments.*.id' => 'sometimes|integer|min:1',
                'installments.*.installment_payment_date' => 'required|date',
                'installments.*.installment_amount' => 'required|numeric|min:0',
                'installments.*.installment_due' => 'sometimes|numeric|min:0',
                'installments.*.installment_payment_status' => 'sometimes|integer|in:0,1',
            ]
        );
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $folder = CrmFolders::findOrFail($folderId);

        $result = DB::transaction(function () use ($installments, $folder) {
            $locked = [];

            foreach ($installments as $row) {
                $row = is_array($row) ? $row : [];
                $mapped = $this->mapInstallmentRow($row, (int) $folder->id);
                $installmentId = isset($row['id']) ? (int) $row['id'] : null;

                if ($installmentId) {
                    $existing = DB::table('crm_folders_installments')
                        ->where('id', $installmentId)
                        ->where('folder_id', (int) $folder->id)
                        ->first();

                    if (!$existing) {
                        DB::table('crm_folders_installments')->insert($mapped);
                        continue;
                    }

                    $existingStatus = (int) ($existing->installment_payment_status ?? 0);
                    if ($existingStatus === 1) {
                        $incomingStatus = (int) ($mapped['installment_payment_status'] ?? 1);
                        $isSame =
                            ($existing->installment_payment_date === $mapped['installment_payment_date']) &&
                            ((float) $existing->installment_amount === (float) $mapped['installment_amount']) &&
                            ((float) $existing->installment_due === (float) $mapped['installment_due']) &&
                            ($incomingStatus === 1);

                        if (!$isSame) {
                            $locked[] = $installmentId;
                        }
                        continue;
                    }

                    DB::table('crm_folders_installments')
                        ->where('id', $installmentId)
                        ->where('folder_id', (int) $folder->id)
                        ->update($mapped);
                } else {
                    DB::table('crm_folders_installments')->insert($mapped);
                }
            }

            if (!empty($locked)) {
                return ['locked' => $locked];
            }

            $list = DB::table('crm_folders_installments')
                ->where('folder_id', (int) $folder->id)
                ->orderBy('installment_payment_date', 'asc')
                ->get();

            return ['data' => $list];
        });

        if (!empty($result['locked'])) {
            return response()->json([
                'status' => false,
                'message' => 'Paid installments cannot be changed',
                'locked_installment_ids' => $result['locked'],
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Installments updated',
            'data' => $result['data'] ?? [],
        ]);
    }

    // UPLOAD PACKAGE PDF + RETURN JSON (no DB writes)
    public function parsePackagePdf(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_pdf' => 'required|file|mimes:pdf|max:20480', // 20MB
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $file = $request->file('package_pdf');
        if (!$file || !$file->isValid()) {
            return response()->json(['status' => false, 'message' => 'Invalid file upload'], 422);
        }

        // Store so frontend can reference it if needed
        $storedPath = $file->store('folder-packages', 'public');
        $absPath = Storage::disk('public')->path($storedPath);

        $parser = new UmrahPackagePdfParser();
        $extracted = $parser->parseFile($absPath);

        return response()->json([
            'status' => true,
            'message' => 'PDF parsed',
            'package_pdf_path' => $storedPath,
            'data' => array_diff_key($extracted, ['raw_text' => true]),
        ]);
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
