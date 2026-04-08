<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmHotel extends Model
{
    protected $table = 'crm_hotels';
    public $timestamps = false;

    protected $fillable = [
        'folder_id',
        'supplier',
        'city',
        'hotel_name',
        'guest_name',
        'no_of_rooms',
        'type',
        'meals',
        'date_in',
        'date_out',
        'nights',
        'suplier_ref',
        'cost',
        'commission',
        'sell',
        'l_status',
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

