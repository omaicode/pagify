<?php

namespace Modules\Media\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\Site;
use Modules\Core\Traits\BelongsToSite;

class MediaAsset extends Model
{
    use BelongsToSite;
    use HasFactory;

    protected $fillable = [
        'site_id',
        'uuid',
        'folder_id',
        'disk',
        'path',
        'path_hash',
        'filename',
        'original_name',
        'mime_type',
        'extension',
        'size_bytes',
        'checksum_sha256',
        'kind',
        'width',
        'height',
        'duration_seconds',
        'meta',
        'alt_text',
        'caption',
        'focal_point_x',
        'focal_point_y',
        'uploaded_by_admin_id',
        'uploaded_at',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration_seconds' => 'float',
        'focal_point_x' => 'float',
        'focal_point_y' => 'float',
        'meta' => 'array',
        'uploaded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $asset): void {
            if ($asset->path !== null && $asset->path !== '') {
                $asset->path_hash = hash('sha256', (string) $asset->path);
            }
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(MediaTag::class, 'media_asset_tag', 'asset_id', 'tag_id')
            ->withTimestamps();
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class, 'asset_id');
    }

    public function transforms(): HasMany
    {
        return $this->hasMany(MediaAssetTransform::class, 'asset_id');
    }
}
