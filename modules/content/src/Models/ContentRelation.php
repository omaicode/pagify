<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\BelongsToSite;

class ContentRelation extends Model
{
    use BelongsToSite;
    use HasFactory;

    protected $fillable = [
        'site_id',
        'source_entry_id',
        'target_entry_id',
        'field_key',
        'relation_type',
        'position',
        'metadata_json',
    ];

    protected $casts = [
        'position' => 'integer',
        'metadata_json' => 'array',
    ];

    public function sourceEntry(): BelongsTo
    {
        return $this->belongsTo(ContentEntry::class, 'source_entry_id');
    }

    public function targetEntry(): BelongsTo
    {
        return $this->belongsTo(ContentEntry::class, 'target_entry_id');
    }

    protected static function newFactory()
    {
        return \Modules\Content\Database\Factories\ContentRelationFactory::new();
    }
}
