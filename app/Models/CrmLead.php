<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CrmLead extends Model
{
    use BelongsToTenant;

    protected $table = 'crm_leads';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
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
        'agent',
        'status',
        'cby',
        'cdate',
        'mby',
        'mdate',
    ];

    public function remarks()
    {
        return $this->hasMany(LeadRemark::class, 'lead_id');
    }
}
