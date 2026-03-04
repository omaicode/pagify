<?php

namespace Modules\Media\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Site;
use Modules\Core\Traits\BelongsToSite;

class MediaUploadSession extends Model
{
    use BelongsToSite;
    use HasFactory;

    protected $fillable = [
        'site_id',
        'uuid',
        'disk',
        'temp_prefix',
        'original_name',
        'mime_type',
        'extension',
        'total_size_bytes',
        'total_chunks',
        'uploaded_chunks',
        'status',
        'folder_id',
        'uploaded_by_admin_id',
        'expires_at',
    ];

    protected $casts = [
        'total_size_bytes' => 'integer',
        'total_chunks' => 'integer',
        'uploaded_chunks' => 'array',
        'expires_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }
}
