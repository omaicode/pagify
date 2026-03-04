<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Admin;
use Modules\Core\Traits\BelongsToSite;

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
        'planned_at',
    ];

    protected $casts = [
        'schema_before_json' => 'array',
        'schema_after_json' => 'array',
        'plan_json' => 'array',
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
        return \Modules\Content\Database\Factories\ContentSchemaMigrationPlanFactory::new();
    }
}
