<?php

namespace Pagify\PageBuilder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Pagify\Core\Models\Admin;
use Pagify\Core\Traits\BelongsToSite;

class SectionTemplate extends Model
{
	use BelongsToSite;
	use HasFactory;
	use SoftDeletes;

	protected $table = 'pb_section_templates';

	protected $fillable = [
		'site_id',
		'name',
		'slug',
		'schema_json',
		'is_active',
		'created_by_admin_id',
	];

	protected $casts = [
		'schema_json' => 'array',
		'is_active' => 'boolean',
	];

	public function creator(): BelongsTo
	{
		return $this->belongsTo(Admin::class, 'created_by_admin_id');
	}
}
