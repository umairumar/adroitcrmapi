<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmCompany extends Model
{
    protected $table = 'crm_company';

    protected $fillable = [
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
