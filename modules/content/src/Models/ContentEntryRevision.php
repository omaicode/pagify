<?php

namespace Pagify\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pagify\Core\Models\Admin;

class ContentEntryRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_entry_id',
        'revision_no',
        'action',
        'snapshot_json',
        'diff_json',
        'created_by_admin_id',
        'metadata_json',
    ];

    protected $casts = [
        'snapshot_json' => 'array',
        'diff_json' => 'array',
        'metadata_json' => 'array',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(ContentEntry::class, 'content_entry_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    protected static function newFactory()
    {
        return \Pagify\Content\Database\Factories\ContentEntryRevisionFactory::new();
    }
}
