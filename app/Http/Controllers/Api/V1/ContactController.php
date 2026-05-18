<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\CrmLead;
use App\Services\Auth\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
    ) {}

    public function index(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'contacts.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $query = Contact::with(['organization', 'tags']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('tag_id')) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $request->tag_id));
        }

        $contacts = $query->orderByDesc('id')->paginate($request->input('per_page', 20));

        return response()->json([
            'status' => true,
            'data' => $contacts->items(),
            'meta' => [
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
            ],
        ]);
    }

    public function show(int $id)
    {
        $contact = Contact::with(['organization', 'tags'])->findOrFail($id);

        return response()->json(['status' => true, 'data' => $contact]);
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
            'type' => 'nullable|in:b2c,b2b',
            'organization_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $contact = Contact::create($request->only([
            'name', 'email', 'phone', 'type', 'organization_id', 'city', 'country', 'metadata',
        ]));

        if ($request->filled('tag_ids')) {
            $contact->tags()->sync($request->tag_ids);
        }

        return response()->json(['status' => true, 'data' => $contact->load('tags')], 201);
    }

    public function update(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'contacts.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $contact = Contact::findOrFail($id);
        $contact->update($request->only([
            'name', 'email', 'phone', 'type', 'organization_id', 'city', 'country', 'metadata',
        ]));

        if ($request->has('tag_ids')) {
            $contact->tags()->sync($request->tag_ids ?? []);
        }

        return response()->json(['status' => true, 'data' => $contact->fresh(['tags', 'organization'])]);
    }

    public function timeline(Request $request, int $id)
    {
        $contact = Contact::findOrFail($id);

        $leads = CrmLead::where('contact_id', $contact->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'contact' => $contact,
                'leads' => $leads,
            ],
        ]);
    }
}
