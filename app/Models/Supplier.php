<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'code', 'email', 'phone', 'type',
        'default_commission_rate', 'status',
    ];

    public function commissionRules(): HasMany
    {
        return $this->hasMany(SupplierCommissionRule::class);
    }
}
