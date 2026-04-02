<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CrmFolders extends Model
{
    protected $table = 'crm_folders';
    public $timestamps = false;
    
    protected $fillable = [
        'order_type','vendor_ref','company','booked_by','invoice_status',
        'closed_on','destination','travel_date','no_of_passengers',
        'ziaraats_makkah','ziaraats_madinah','balanceduedate',
        'sell','cost','commission','remaining',
        'lock_passengers','lock_hotels','lock_transport','lock_others',
        'cby','cdate','mby','mdate'
    ];

    public function itineraries()
    {
        return $this->hasMany(CrmItinerary::class, 'folder_id');
    }

    public function passengers()
    {
        return $this->hasMany(CrmPassengersName::class, 'folder_id');
    }
}
