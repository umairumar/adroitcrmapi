<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmOther extends Model
{
    protected $table = 'crm_others';
    public $timestamps = false;

    protected $fillable = [
        'folder_id',
        'supplier',
        'description',
        'cost',
        'commission',
        'sell',
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

