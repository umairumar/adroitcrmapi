<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiUsageLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_api_key_id', 'endpoint', 'method', 'status_code', 'ip_address', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(TenantApiKey::class, 'tenant_api_key_id');
    }
}
