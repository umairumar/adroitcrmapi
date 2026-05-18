<?php

namespace App\Services\Integrations;

use App\Models\Tenant;
use App\Models\TenantBranding;

class WhiteLabelService
{
    public function forTenant(int $tenantId): TenantBranding
    {
        return TenantBranding::firstOrCreate(
            ['tenant_id' => $tenantId],
            array_merge(
                ['tenant_id' => $tenantId],
                config('integrations.default_branding', [])
            )
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $tenantId, array $data): TenantBranding
    {
        $branding = $this->forTenant($tenantId);
        $branding->update(collect($data)->only([
            'app_name', 'logo_url', 'favicon_url',
            'primary_color', 'secondary_color', 'accent_color',
            'custom_domain', 'support_email', 'support_phone',
            'email_from_name', 'email_footer_html', 'custom_css', 'is_active',
        ])->filter(fn ($v) => $v !== null)->all());

        return $branding->fresh();
    }

    public function resolvePublic(string $slugOrDomain): ?array
    {
        $tenant = Tenant::where('slug', $slugOrDomain)->first();

        if (! $tenant) {
            $branding = TenantBranding::withoutGlobalScopes()
                ->where('custom_domain', $slugOrDomain)
                ->where('is_active', true)
                ->first();

            if ($branding) {
                $tenant = Tenant::find($branding->tenant_id);
            }
        }

        if (! $tenant || $tenant->status !== 'active') {
            return null;
        }

        $branding = $this->forTenant($tenant->id);

        if (! $branding->is_active) {
            return null;
        }

        return [
            'tenant' => [
                'slug' => $tenant->slug,
                'name' => $tenant->name,
            ],
            'branding' => $branding->only([
                'app_name', 'logo_url', 'favicon_url',
                'primary_color', 'secondary_color', 'accent_color',
                'support_email', 'support_phone', 'custom_css',
            ]),
        ];
    }
}
