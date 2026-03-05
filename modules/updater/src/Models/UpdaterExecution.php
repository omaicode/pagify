<?php

namespace Pagify\Updater\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Pagify\Core\Models\Admin;

class UpdaterExecution extends Model
{
    protected $table = 'updater_executions';

    protected $fillable = [
        'requested_by_admin_id',
        'target_type',
        'target_value',
        'status',
        'meta',
        'result',
        'snapshot_path',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'result' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'requested_by_admin_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(UpdaterExecutionItem::class, 'execution_id');
    }
}
