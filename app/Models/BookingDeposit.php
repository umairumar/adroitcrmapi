<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingDeposit extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'folder_id', 'label', 'deposit_type', 'amount',
        'paid_amount', 'due_date', 'status', 'legacy_installment_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(CrmFolders::class, 'folder_id');
    }

    public function remaining(): float
    {
        return max(0, (float) $this->amount - (float) $this->paid_amount);
    }
}
