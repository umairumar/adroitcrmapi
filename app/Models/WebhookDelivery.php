<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    protected $fillable = [
        'endpoint_id', 'event', 'payload', 'status', 'attempts',
        'response_code', 'response_body', 'error_message',
        'next_retry_at', 'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'next_retry_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(TenantWebhookEndpoint::class, 'endpoint_id');
    }
}
