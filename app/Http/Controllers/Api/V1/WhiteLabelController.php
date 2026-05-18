<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthorizationService;
use App\Services\Integrations\WhiteLabelService;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\Request;

class WhiteLabelController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly WhiteLabelService $whiteLabel,
    ) {}

    public function show(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'white_label.manage')
            && ! $this->authz->hasPermission($request->user(), 'tenant.settings')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $tenantId = TenantContext::id() ?? $request->user()->tenant_id;

        return response()->json([
            'status' => true,
            'data' => $this->whiteLabel->forTenant((int) $tenantId),
        ]);
    }

    public function update(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'white_label.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate([
            'app_name' => 'nullable|string|max:120',
            'logo_url' => 'nullable|url',
            'favicon_url' => 'nullable|url',
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'accent_color' => 'nullable|string|max:20',
            'custom_domain' => 'nullable|string|max:255',
            'support_email' => 'nullable|email',
            'support_phone' => 'nullable|string|max:50',
            'email_from_name' => 'nullable|string|max:120',
            'email_footer_html' => 'nullable|string',
            'custom_css' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'company_address' => 'nullable|string',
            'company_registration' => 'nullable|string|max:80',
            'vat_number' => 'nullable|string|max:40',
            'invoice_bank_name' => 'nullable|string|max:120',
            'invoice_sort_code' => 'nullable|string|max:20',
            'invoice_account_number' => 'nullable|string|max:40',
            'invoice_iban' => 'nullable|string|max:50',
            'invoice_payment_instructions' => 'nullable|string',
            'invoice_terms' => 'nullable|string',
        ]);

        $tenantId = (int) (TenantContext::id() ?? $request->user()->tenant_id);
        $branding = $this->whiteLabel->update($tenantId, $request->all());

        return response()->json(['status' => true, 'data' => $branding]);
    }

    public function publicBranding(string $slug)
    {
        $data = $this->whiteLabel->resolvePublic($slug);

        if (! $data) {
            return response()->json(['status' => false, 'message' => 'Not found'], 404);
        }

        return response()->json(['status' => true, 'data' => $data]);
    }
}
