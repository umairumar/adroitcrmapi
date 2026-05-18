<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TenantBranding extends Model
{
    use BelongsToTenant;

    protected $table = 'tenant_branding';

    protected $fillable = [
        'tenant_id', 'app_name', 'logo_url', 'favicon_url',
        'primary_color', 'secondary_color', 'accent_color',
        'custom_domain', 'support_email', 'support_phone',
        'email_from_name', 'email_footer_html', 'custom_css', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
