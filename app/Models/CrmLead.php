<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmLead extends Model
{
    use BelongsToTenant;

    protected $table = 'crm_leads';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'pipeline_stage_id',
        'contact_id',
        'organization_id',
        'departure',
        'destination',
        'class',
        'ddate',
        'rdate',
        'lead_details',
        'name',
        'email',
        'phone',
        'adult',
        'child',
        'infant',
        'journey',
        'brand',
        'night_makkah',
        'night_madinah',
        'rooms',
        'accomodation',
        'company',
        'message',
        'lead_type',
        'source',
        'source_detail',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'referral_code',
        'estimated_value',
        'duplicate_of_lead_id',
        'agent',
        'status',
        'assigned_at',
        'stage_entered_at',
        'cby',
        'cdate',
        'mby',
        'mdate',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'stage_entered_at' => 'datetime',
            'estimated_value' => 'decimal:2',
        ];
    }

    public function remarks()
    {
        return $this->hasMany(LeadRemark::class, 'lead_id');
    }

    public function pipelineStage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
