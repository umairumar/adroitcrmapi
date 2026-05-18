<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConversationThread extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'contact_id', 'lead_id', 'channel', 'external_id',
        'status', 'assigned_user_id', 'last_message_at',
    ];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'thread_id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function lead()
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }
}
