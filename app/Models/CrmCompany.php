<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CrmCompany extends Model
{
    use BelongsToTenant;

    protected $table = 'crm_company';

    protected $fillable = [
        'tenant_id',
        'title',
        'address',
        'email',
        'phone',
        'status',
        'subscribe_link',
        'web_address',
        'image'
    ];

    public $timestamps = false; // because table has no created_at, updated_at
}
