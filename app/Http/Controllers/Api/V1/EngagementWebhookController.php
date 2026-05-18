<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\CrmLead;
use App\Services\Engagement\MessagingService;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\Request;

class EngagementWebhookController extends Controller
{
    public function __construct(
        private readonly MessagingService $messaging,
    ) {}

    /**
     * Inbound message webhook (WhatsApp/SMS/Messenger stubs).
     */
    public function inbound(Request $request, string $channel)
    {
        $secret = config('engagement.webhook_secret');
        if ($secret && $request->header('X-Webhook-Secret') !== $secret) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'body' => 'required|string',
            'tenant_id' => 'required|integer',
            'contact_id' => 'nullable|integer',
            'lead_id' => 'nullable|integer',
            'email' => 'nullable|string',
            'phone' => 'nullable|string',
            'external_id' => 'nullable|string',
        ]);

        TenantContext::set((int) $request->tenant_id);
        TenantContext::disableBypass();

        $contactId = $request->contact_id;
        $leadId = $request->lead_id;

        if (! $contactId && ! $leadId) {
            if ($request->email) {
                $contactId = Contact::withoutGlobalScopes()
                    ->where('tenant_id', $request->tenant_id)
                    ->where('email', $request->email)
                    ->value('id');
            }
            if (! $contactId && $request->phone) {
                $contactId = Contact::withoutGlobalScopes()
                    ->where('tenant_id', $request->tenant_id)
                    ->where('phone', $request->phone)
                    ->value('id');
            }
            if (! $contactId && $request->phone) {
                $leadId = CrmLead::withoutGlobalScopes()
                    ->where('tenant_id', $request->tenant_id)
                    ->where('phone', $request->phone)
                    ->value('id');
            }
        }

        $thread = $this->messaging->findOrCreateThread(
            $channel,
            $contactId,
            $leadId,
            (int) $request->tenant_id,
        );

        if ($request->external_id) {
            $thread->update(['external_id' => $request->external_id]);
        }

        $message = $this->messaging->recordInbound($thread, $request->body, $request->external_id);

        return response()->json(['status' => true, 'data' => $message], 201);
    }
}
