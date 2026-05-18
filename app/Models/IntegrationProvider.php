<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntegrationProvider extends Model
{
    protected $fillable = [
        'slug', 'name', 'type', 'description', 'logo_url', 'config_schema', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'config_schema' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function tenantIntegrations(): HasMany
    {
        return $this->hasMany(TenantIntegration::class, 'provider_id');
    }
}
