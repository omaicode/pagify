<?php

namespace Pagify\PageBuilder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pagify\Core\Models\Admin;

class PageRevision extends Model
{
	use HasFactory;

	protected $table = 'pb_page_revisions';

	protected $fillable = [
		'page_id',
		'revision_no',
		'action',
		'snapshot_json',
		'diff_json',
		'created_by_admin_id',
		'metadata_json',
	];

	protected $casts = [
		'snapshot_json' => 'array',
		'diff_json' => 'array',
		'metadata_json' => 'array',
	];

	public function page(): BelongsTo
	{
		return $this->belongsTo(Page::class, 'page_id');
	}

	public function admin(): BelongsTo
	{
		return $this->belongsTo(Admin::class, 'created_by_admin_id');
	}
}
