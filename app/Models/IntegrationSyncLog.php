<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationSyncLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_integration_id', 'sync_type', 'status',
        'request_summary', 'response_summary', 'records_processed', 'error_message', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'request_summary' => 'array',
            'response_summary' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(TenantIntegration::class, 'tenant_integration_id');
    }
}
