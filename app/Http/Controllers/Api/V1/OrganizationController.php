<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\Auth\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
    ) {}

    public function index(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'contacts.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $orgs = Organization::withCount('contacts')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->orderBy('name')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'status' => true,
            'data' => $orgs->items(),
            'meta' => [
                'current_page' => $orgs->currentPage(),
                'last_page' => $orgs->lastPage(),
                'total' => $orgs->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'contacts.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'policies' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $org = Organization::create($request->only(['name', 'email', 'phone', 'address', 'policies', 'status']));

        return response()->json(['status' => true, 'data' => $org], 201);
    }

    public function show(int $id)
    {
        $org = Organization::with('contacts')->findOrFail($id);

        return response()->json(['status' => true, 'data' => $org]);
    }

    public function update(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'contacts.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $org = Organization::findOrFail($id);
        $org->update($request->only(['name', 'email', 'phone', 'address', 'policies', 'status']));

        return response()->json(['status' => true, 'data' => $org]);
    }
}
