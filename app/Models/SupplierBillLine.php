<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierBillLine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'supplier_bill_id', 'description', 'amount', 'tax_rate_id', 'tax_amount',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'tax_amount' => 'decimal:2'];
    }
}
