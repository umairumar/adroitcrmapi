<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerInvoice extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'folder_id', 'contact_id', 'invoice_number', 'issue_date', 'due_date',
        'currency', 'fx_rate', 'subtotal', 'tax_amount', 'total', 'amount_paid', 'status',
        'revenue_recognition', 'recognized_at', 'journal_entry_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'recognized_at' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'fx_rate' => 'decimal:8',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(CrmFolders::class, 'folder_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CustomerInvoiceLine::class);
    }

    public function balanceDue(): float
    {
        return max(0, (float) $this->total - (float) $this->amount_paid);
    }
}
