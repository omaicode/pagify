<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentField extends Model
{
    use HasFactory;

    protected $table = 'content_type_fields';

    protected $fillable = [
        'content_type_id',
        'key',
        'label',
        'field_type',
        'config_json',
        'validation_json',
        'conditional_json',
        'sort_order',
        'is_required',
        'is_localized',
    ];

    protected $casts = [
        'config_json' => 'array',
        'validation_json' => 'array',
        'conditional_json' => 'array',
        'is_required' => 'boolean',
        'is_localized' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function contentType(): BelongsTo
    {
        return $this->belongsTo(ContentType::class);
    }

    protected static function newFactory()
    {
        return \Modules\Content\Database\Factories\ContentFieldFactory::new();
    }
}
