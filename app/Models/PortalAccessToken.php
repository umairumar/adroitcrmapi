<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class PortalAccessToken extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'contact_id', 'token', 'expires_at', 'last_used_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }
}
