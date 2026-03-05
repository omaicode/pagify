<?php

namespace Pagify\Updater\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpdaterExecutionItem extends Model
{
    protected $table = 'updater_execution_items';

    protected $fillable = [
        'execution_id',
        'module_slug',
        'package_name',
        'status',
        'from_version',
        'to_version',
        'error_message',
    ];

    public function execution(): BelongsTo
    {
        return $this->belongsTo(UpdaterExecution::class, 'execution_id');
    }
}
