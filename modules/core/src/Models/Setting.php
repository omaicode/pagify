<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use HasFactory;

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
