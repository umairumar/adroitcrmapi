<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ClientFeedback extends Model
{
    use BelongsToTenant;

    protected $table = 'client_feedback';

    protected $fillable = ['tenant_id', 'contact_id', 'folder_id', 'rating', 'comment'];
}
