<?php

namespace Pagify\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pagify\Core\Models\Admin;
use Pagify\Core\Traits\BelongsToSite;

class ContentSchemaMigrationPlan extends Model
{
    use BelongsToSite;
    use HasFactory;

    protected $fillable = [
        'site_id',
        'content_type_id',
        'requested_by_admin_id',
        'status',
        'schema_before_json',
        'schema_after_json',
        'plan_json',
        'error_message',
        'execution_hash',
        'execution_attempts',
        'execution_started_at',
        'executed_at',
        'planned_at',
    ];

    protected $casts = [
        'schema_before_json' => 'array',
        'schema_after_json' => 'array',
        'plan_json' => 'array',
        'execution_attempts' => 'integer',
        'execution_started_at' => 'datetime',
        'executed_at' => 'datetime',
        'planned_at' => 'datetime',
    ];

    public function contentType(): BelongsTo
    {
        return $this->belongsTo(ContentType::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'requested_by_admin_id');
    }

    protected static function newFactory()
    {
        return \Pagify\Content\Database\Factories\ContentSchemaMigrationPlanFactory::new();
    }
}
