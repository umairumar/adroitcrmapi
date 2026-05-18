<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'branch_id', 'account_id', 'fiscal_year', 'period_month', 'amount',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }
}
