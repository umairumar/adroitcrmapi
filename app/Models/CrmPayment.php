<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CrmPayment extends Model
{
    use BelongsToTenant;

    protected $table = 'crm_payments';
    public $timestamps = false;
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'tenant_id',
        'folder_id',
        'payment_type',
        'booking_deposit_id',
        'payment',
        'pdate',
        'payment_mode',
        'proof',
        'status',
        'remarks',
        'cby',
        'cdate',
        'process_by',
        'process_date',
    ];

    public function folder()
    {
        return $this->belongsTo(CrmFolders::class, 'folder_id');
    }
}

