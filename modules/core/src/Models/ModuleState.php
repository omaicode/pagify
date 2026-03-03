<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleState extends Model
{
    use HasFactory;

    protected $table = 'core_module_states';

    protected $fillable = [
        'module',
        'enabled',
        'metadata',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'metadata' => 'array',
    ];
}
