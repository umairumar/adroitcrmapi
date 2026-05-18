<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantWebhookEndpoint extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'url', 'secret', 'events', 'description', 'is_active',
    ];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'endpoint_id');
    }

    public function subscribesTo(string $event): bool
    {
        $events = $this->events ?? [];

        return in_array($event, $events, true) || in_array('*', $events, true);
    }
}
