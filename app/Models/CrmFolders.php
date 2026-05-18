<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\CrmHotel;
use App\Models\CrmPassengers;
use App\Models\CrmPassengersName;
use App\Models\CrmTransport;
use App\Models\CrmOther;
use App\Models\CrmPayment;

class CrmFolders extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $table = 'crm_folders';
    public $timestamps = true;
    
    protected $fillable = [
        'tenant_id',
        'order_type','vendor_ref','company','booked_by','invoice_status',
        'closed_on','destination','travel_date','no_of_passengers',
        'ziaraats_makkah','ziaraats_madinah','balanceduedate',
        'sell','cost','commission','remaining',
        'lock_passengers','lock_hotels','lock_transport','lock_others',
        'cby','cdate','mby','mdate',
    ];

    public function itineraries()
    {
        return $this->hasMany(CrmItinerary::class, 'folder_id');
    }

    public function passengers()
    {
        return $this->hasMany(CrmPassengers::class, 'folder_id');
    }

    public function passengersNames()
    {
        return $this->hasMany(CrmPassengersName::class, 'folder_id');
    }

    public function hotels()
    {
        return $this->hasMany(CrmHotel::class, 'folder_id');
    }

    public function transport()
    {
        return $this->hasMany(CrmTransport::class, 'folder_id');
    }

    public function others()
    {
        return $this->hasMany(CrmOther::class, 'folder_id');
    }

    public function payments()
    {
        return $this->hasMany(CrmPayment::class, 'folder_id');
    }
}
