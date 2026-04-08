<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmPassengers extends Model
{
    protected $table = 'crm_passengers';
    public $timestamps = false;

    protected $fillable = [
        'folder_id',
        'pax',
        'ticket_no',
        'ticket_date',
        'airline_from',
        'airline_to',
        'fare',
        'tax',
        'total_cost',
        'commission',
        'sell',
        'supplier',
        'status',
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

