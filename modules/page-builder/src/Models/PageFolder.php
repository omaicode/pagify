<?php

namespace Pagify\PageBuilder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Pagify\Core\Traits\BelongsToSite;

class PageFolder extends Model
{
	use BelongsToSite;
	use HasFactory;

	protected $table = 'pb_page_folders';

	protected $fillable = [
		'site_id',
		'folder_id',
		'name',
		'slug',
		'parent_folder_id',
		'sort_order',
	];

	protected $casts = [
		'sort_order' => 'integer',
	];
}
