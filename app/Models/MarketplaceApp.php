<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceApp extends Model
{
    protected $fillable = [
        'slug', 'name', 'description', 'category', 'scopes',
        'rate_limit_per_minute', 'is_public', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantApiApp::class);
    }
}
