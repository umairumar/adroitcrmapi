<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierBill extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'supplier_id', 'folder_id', 'bill_number', 'supplier_reference',
        'issue_date', 'due_date', 'currency', 'subtotal', 'tax_amount', 'total',
        'amount_paid', 'status', 'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SupplierBillLine::class);
    }
}
