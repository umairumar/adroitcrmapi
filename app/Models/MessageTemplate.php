<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'channel', 'subject', 'body', 'variables', 'is_active'];

    protected function casts(): array
    {
        return ['variables' => 'array', 'is_active' => 'boolean'];
    }
}
