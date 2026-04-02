<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmPassengersName extends Model
{
    protected $table = 'crm_passengers_name';
    public $timestamps = false;

    protected $fillable = [
        'folder_id','title','fname','mname','lname','type','email','phone',
        'dob','passport','cby','cdate','mby','mdate'
    ];

    public function folder()
    {
        return $this->belongsTo(CrmFolders::class, 'folder_id');
    }
}
