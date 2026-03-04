<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Traits\BelongsToSite;

class ContentEntry extends Model
{
    use BelongsToSite;
    use HasFactory;

    protected $fillable = [
        'site_id',
        'content_type_id',
        'slug',
        'status',
        'published_at',
        'unpublished_at',
        'scheduled_publish_at',
        'scheduled_unpublish_at',
        'data_json',
        'schedule_metadata_json',
    ];

    protected $casts = [
        'data_json' => 'array',
        'schedule_metadata_json' => 'array',
        'published_at' => 'datetime',
        'unpublished_at' => 'datetime',
        'scheduled_publish_at' => 'datetime',
        'scheduled_unpublish_at' => 'datetime',
    ];

    public function contentType(): BelongsTo
    {
        return $this->belongsTo(ContentType::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ContentEntryRevision::class, 'content_entry_id')
            ->orderByDesc('revision_no');
    }

    public function outgoingRelations(): HasMany
    {
        return $this->hasMany(ContentRelation::class, 'source_entry_id')
            ->orderBy('field_key')
            ->orderBy('position');
    }

    public function incomingRelations(): HasMany
    {
        return $this->hasMany(ContentRelation::class, 'target_entry_id');
    }
}
