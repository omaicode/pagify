<?php

namespace Pagify\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PluginState extends Model
{
    use HasFactory;

    protected $table = 'core_plugin_states';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'version',
        'package_name',
        'source_type',
        'root_path',
        'enabled',
        'is_installed',
        'is_compatible',
        'compatibility_issues',
        'manifest',
        'safe_mode_disabled_at',
        'last_error',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'is_installed' => 'boolean',
        'is_compatible' => 'boolean',
        'compatibility_issues' => 'array',
        'manifest' => 'array',
        'safe_mode_disabled_at' => 'datetime',
    ];
}
