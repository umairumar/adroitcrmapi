<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignRecipient extends Model
{
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    protected $fillable = [
        'campaign_id', 'contact_id', 'lead_id', 'email', 'phone',
        'status', 'current_step', 'next_send_at',
    ];

    protected function casts(): array
    {
        return ['next_send_at' => 'datetime'];
    }
}
