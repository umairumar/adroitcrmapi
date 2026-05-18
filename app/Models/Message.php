<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'thread_id', 'direction', 'channel', 'body', 'status',
        'external_id', 'metadata', 'sent_by', 'sent_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'sent_at' => 'datetime'];
    }

    public function thread()
    {
        return $this->belongsTo(ConversationThread::class, 'thread_id');
    }
}
