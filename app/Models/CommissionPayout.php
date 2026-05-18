<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionPayout extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'user_id', 'supplier_id', 'recipient_type',
        'period_start', 'period_end', 'total_amount', 'status',
        'paid_at', 'payment_reference', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'total_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CommissionEntry::class, 'payout_id');
    }
}
