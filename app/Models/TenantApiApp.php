<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantApiApp extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'marketplace_app_id', 'name', 'scopes', 'status', 'subscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'subscribed_at' => 'datetime',
        ];
    }

    public function marketplaceApp(): BelongsTo
    {
        return $this->belongsTo(MarketplaceApp::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(TenantApiKey::class);
    }

    public function effectiveScopes(): array
    {
        if (! empty($this->scopes)) {
            return $this->scopes;
        }

        return $this->marketplaceApp?->scopes ?? [];
    }
}
