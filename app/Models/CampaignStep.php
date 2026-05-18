<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignStep extends Model
{
    protected $fillable = ['campaign_id', 'step_order', 'delay_hours', 'template_id'];

    public function template()
    {
        return $this->belongsTo(MessageTemplate::class, 'template_id');
    }
}
