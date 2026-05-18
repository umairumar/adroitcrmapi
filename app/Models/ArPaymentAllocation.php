<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ArPaymentAllocation extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'customer_invoice_id', 'crm_payment_id', 'amount', 'allocated_date',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'allocated_date' => 'date'];
    }
}
