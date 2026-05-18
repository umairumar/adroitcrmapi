<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\LoyaltyTransaction;
use App\Services\Auth\AuthorizationService;
use App\Services\Engagement\LoyaltyService;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly LoyaltyService $loyalty,
    ) {}

    public function transactions(Request $request, int $contactId)
    {
        if (! $this->authz->hasPermission($request->user(), 'loyalty.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $contact = Contact::findOrFail($contactId);

        return response()->json([
            'status' => true,
            'data' => [
                'contact' => $contact->only(['id', 'name', 'email', 'loyalty_points']),
                'transactions' => LoyaltyTransaction::where('contact_id', $contactId)
                    ->orderByDesc('created_at')
                    ->limit(50)
                    ->get(),
            ],
        ]);
    }

    public function earn(Request $request, int $contactId)
    {
        if (! $this->authz->hasPermission($request->user(), 'loyalty.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate([
            'points' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
        ]);

        $contact = Contact::findOrFail($contactId);
        $this->loyalty->earn($contact, (int) $request->points, $request->reason);

        return response()->json([
            'status' => true,
            'data' => $contact->fresh()->only(['id', 'loyalty_points']),
        ]);
    }

    public function redeem(Request $request, int $contactId)
    {
        if (! $this->authz->hasPermission($request->user(), 'loyalty.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate([
            'points' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
        ]);

        $contact = Contact::findOrFail($contactId);
        if (! $this->loyalty->redeem($contact, (int) $request->points, $request->reason)) {
            return response()->json(['status' => false, 'message' => 'Insufficient points'], 422);
        }

        return response()->json([
            'status' => true,
            'data' => $contact->fresh()->only(['id', 'loyalty_points']),
        ]);
    }
}
