<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Segment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'entity_type', 'filters', 'created_by',
    ];

    protected function casts(): array
    {
        return ['filters' => 'array'];
    }
}
