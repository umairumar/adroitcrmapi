<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'channel', 'segment_id', 'template_id', 'status',
        'scheduled_at', 'started_at', 'completed_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(CampaignStep::class)->orderBy('step_order');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }
}
