<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantIntegration extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'provider_id', 'label', 'credentials', 'settings',
        'status', 'last_synced_at', 'last_error',
    ];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(IntegrationProvider::class, 'provider_id');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(IntegrationSyncLog::class);
    }
}
