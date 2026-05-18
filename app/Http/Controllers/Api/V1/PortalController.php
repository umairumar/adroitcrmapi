<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClientFeedback;
use App\Models\Contact;
use App\Models\CrmFolders;
use App\Services\Auth\AuthorizationService;
use App\Services\Engagement\PortalService;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function __construct(
        private readonly PortalService $portal,
        private readonly AuthorizationService $authz,
    ) {}

    /**
     * Public: validate magic link token and return portal session payload.
     */
    public function auth(string $token)
    {
        $access = $this->portal->authenticate($token);
        if (! $access) {
            return response()->json(['status' => false, 'message' => 'Invalid or expired portal token'], 401);
        }

        $contact = Contact::withoutGlobalScopes()->find($access->contact_id);
        if (! $contact) {
            return response()->json(['status' => false, 'message' => 'Contact not found'], 404);
        }

        return response()->json([
            'status' => true,
            'token' => $access->token,
            'expires_at' => $access->expires_at,
            'data' => $this->portal->dashboard($contact),
        ]);
    }

    public function dashboard(Request $request)
    {
        $contact = $request->attributes->get('portal_contact');

        return response()->json([
            'status' => true,
            'data' => $this->portal->dashboard($contact),
        ]);
    }

    public function booking(Request $request, int $id)
    {
        $contact = $request->attributes->get('portal_contact');
        $folder = CrmFolders::withoutGlobalScopes()
            ->where('tenant_id', $contact->tenant_id)
            ->findOrFail($id);

        $dashboard = $this->portal->dashboard($contact);
        $allowedIds = collect($dashboard['bookings'])->pluck('id');
        if (! $allowedIds->contains($folder->id)) {
            return response()->json(['status' => false, 'message' => 'Booking not found'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->portal->bookingDetail($folder, $contact),
        ]);
    }

    public function feedback(Request $request)
    {
        $contact = $request->attributes->get('portal_contact');

        $request->validate([
            'rating' => 'nullable|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'folder_id' => 'nullable|integer',
        ]);

        $feedback = ClientFeedback::create([
            'tenant_id' => $contact->tenant_id,
            'contact_id' => $contact->id,
            'folder_id' => $request->folder_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json(['status' => true, 'data' => $feedback], 201);
    }

    public function issueLink(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'portal.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate(['contact_id' => 'required|integer']);

        $contact = Contact::findOrFail($request->contact_id);

        return response()->json([
            'status' => true,
            'data' => $this->portal->issueMagicLink($contact),
        ]);
    }
}
