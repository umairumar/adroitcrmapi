<?php

namespace App\Http\Middleware;

use App\Models\Contact;
use App\Models\PortalAccessToken;
use App\Services\Engagement\PortalService;
use App\Services\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PortalAuth
{
    public function __construct(
        private readonly PortalService $portal,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken()
            ?? $request->header('X-Portal-Token')
            ?? $request->route('token');

        if (! $token) {
            return response()->json(['status' => false, 'message' => 'Portal token required'], 401);
        }

        $access = $this->portal->authenticate($token);
        if (! $access) {
            return response()->json(['status' => false, 'message' => 'Invalid or expired portal token'], 401);
        }

        $contact = Contact::withoutGlobalScopes()->with('tenant')->find($access->contact_id);
        if (! $contact || ! $contact->tenant) {
            return response()->json(['status' => false, 'message' => 'Contact not found'], 404);
        }

        TenantContext::setFromTenant($contact->tenant);
        TenantContext::disableBypass();

        $request->attributes->set('portal_contact', $contact);
        $request->attributes->set('portal_access', $access);

        return $next($request);
    }
}
