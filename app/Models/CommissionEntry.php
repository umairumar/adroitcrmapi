<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionEntry extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'folder_id', 'recipient_type', 'user_id', 'supplier_id',
        'rule_id', 'rule_type', 'base_amount', 'rate', 'amount', 'status',
        'payout_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:2',
            'rate' => 'decimal:4',
            'amount' => 'decimal:2',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(CrmFolders::class, 'folder_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
