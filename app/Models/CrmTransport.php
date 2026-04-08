<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmTransport extends Model
{
    protected $table = 'crm_transport';
    public $timestamps = false;

    protected $fillable = [
        'folder_id',
        'supplier',
        'description',
        'tfrom',
        'tto',
        'tdate',
        'pickup_time',
        'vehicle_type',
        'cost',
        'commission',
        'sell',
        'sar',
        'cby',
        'cdate',
        'mby',
        'mdate',
    ];

    public function folder()
    {
        return $this->belongsTo(CrmFolders::class, 'folder_id');
    }
}

