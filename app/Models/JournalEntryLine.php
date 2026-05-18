<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryLine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'journal_entry_id', 'account_id', 'debit', 'credit',
        'description', 'folder_id', 'currency', 'fx_rate',
    ];

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'fx_rate' => 'decimal:8',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
