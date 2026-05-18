<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingDocument extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'folder_id', 'title', 'document_type', 'file_path',
        'mime_type', 'uploaded_by',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(CrmFolders::class, 'folder_id');
    }
}
