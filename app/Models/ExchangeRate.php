<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'from_currency', 'to_currency', 'rate', 'rate_date'];

    protected function casts(): array
    {
        return ['rate' => 'decimal:8', 'rate_date' => 'date'];
    }
}
