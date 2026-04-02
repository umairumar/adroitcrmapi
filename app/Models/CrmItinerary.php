<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmItinerary extends Model
{
    protected $table = 'crm_itinerary';
    public $timestamps = false;

    protected $fillable = [
        'folder_id','srno','itin_no','airline_code','airline_no','class',
        'departure_date','departure_airport','arival_airport',
        'departure_time','arrival_time','arrival_date',
        'cby','cdate','mby','mdate'
    ];
    

    public function folder()
    {
        return $this->belongsTo(CrmFolders::class, 'folder_id');
    }
}
