<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\Auth\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
    ) {}

    public function index(Request $request)
    {
        $q = Supplier::query()->orderBy('name');
        if ($request->filled('search')) {
            $q->where('name', 'like', '%' . $request->search . '%');
        }

        return response()->json(['status' => true, 'data' => $q->get()]);
    }

    public function store(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'suppliers.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), ['name' => 'required|string|max:255']);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $supplier = Supplier::create($request->only([
            'name', 'code', 'email', 'phone', 'type', 'default_commission_rate', 'status',
        ]));

        return response()->json(['status' => true, 'data' => $supplier], 201);
    }

    public function update(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'suppliers.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $supplier = Supplier::findOrFail($id);
        $supplier->update($request->only([
            'name', 'code', 'email', 'phone', 'type', 'default_commission_rate', 'status',
        ]));

        return response()->json(['status' => true, 'data' => $supplier]);
    }
}
