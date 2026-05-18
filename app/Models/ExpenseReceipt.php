<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseReceipt extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'folder_id', 'user_id', 'category', 'amount', 'currency',
        'receipt_date', 'file_path', 'vendor_name', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'receipt_date' => 'date',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(CrmFolders::class, 'folder_id');
    }
}
