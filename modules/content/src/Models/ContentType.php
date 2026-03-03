<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Traits\BelongsToSite;

class ContentType extends Model
{
    use BelongsToSite;
    use HasFactory;

    protected $fillable = [
        'site_id',
        'name',
        'slug',
        'description',
        'is_active',
        'schema_json',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'schema_json' => 'array',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(ContentField::class)->orderBy('sort_order');
    }
}
