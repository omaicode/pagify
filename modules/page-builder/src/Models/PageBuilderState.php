<?php

namespace Pagify\PageBuilder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pagify\Core\Traits\BelongsToSite;

class PageBuilderState extends Model
{
	use BelongsToSite;
	use HasFactory;

	protected $table = 'pb_page_builder_states';

	protected $fillable = [
		'site_id',
		'page_id',
		'build_id',
		'version',
		'data_json',
	];

	protected $casts = [
		'version' => 'integer',
		'data_json' => 'array',
	];

	public function page(): BelongsTo
	{
		return $this->belongsTo(Page::class, 'page_id');
	}
}
