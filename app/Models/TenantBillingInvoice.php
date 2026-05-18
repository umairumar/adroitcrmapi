<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantBillingInvoice extends Model
{
    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'plan',
        'period_start',
        'period_end',
        'amount',
        'currency',
        'status',
        'issue_date',
        'due_date',
        'paid_at',
        'payment_reference',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'issue_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, ['sent', 'overdue'], true)
            && $this->due_date->isPast();
    }
}
