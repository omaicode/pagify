<?php

namespace Pagify\PageBuilder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Pagify\Core\Models\Admin;
use Pagify\Core\Traits\BelongsToSite;

class Page extends Model
{
	use BelongsToSite;
	use HasFactory;
	use SoftDeletes;

	protected $table = 'pb_pages';

	protected $fillable = [
		'site_id',
		'title',
		'slug',
		'status',
		'layout_json',
		'seo_meta_json',
		'published_at',
		'created_by_admin_id',
		'updated_by_admin_id',
	];

	protected $casts = [
		'layout_json' => 'array',
		'seo_meta_json' => 'array',
		'published_at' => 'datetime',
	];

	public function creator(): BelongsTo
	{
		return $this->belongsTo(Admin::class, 'created_by_admin_id');
	}

	public function updater(): BelongsTo
	{
		return $this->belongsTo(Admin::class, 'updated_by_admin_id');
	}
}
