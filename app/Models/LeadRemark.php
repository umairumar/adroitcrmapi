<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadRemark extends Model
{
    protected $table = 'crm_leads_remarks';

    public $timestamps = false;

    protected $fillable = [
        'lead_id',
        'remarks',
        'cby',
        'cdate'
    ];
}
