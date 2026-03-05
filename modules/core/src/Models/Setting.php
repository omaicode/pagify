<?php

namespace Pagify\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pagify\Core\Traits\BelongsToSite;

class Setting extends Model
{
    use BelongsToSite;
    use HasFactory;

    protected bool $allowGlobalSiteRecords = true;

    protected $fillable = [
        'site_id',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
