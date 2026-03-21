<?php

namespace Pagify\PageBuilder\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Pagify\PageBuilder\Models\PageFolder;

class PageFolderRepository
{
	public function listBySite(?int $siteId): Collection
	{
		return PageFolder::query()
			->when($siteId !== null, static fn ($query) => $query->where('site_id', $siteId))
			->orderByRaw('parent_folder_id IS NULL DESC')
			->orderBy('parent_folder_id')
			->orderBy('sort_order')
			->orderBy('id')
			->get();
	}

	public function findByFolderId(string $folderId, ?int $siteId): ?PageFolder
	{
		return PageFolder::query()
			->when($siteId !== null, static fn ($query) => $query->where('site_id', $siteId))
			->where('folder_id', $folderId)
			->first();
	}

	public function nextSortOrder(?int $siteId, ?string $parentFolderId): int
	{
		$maxSort = PageFolder::query()
			->when($siteId !== null, static fn ($query) => $query->where('site_id', $siteId))
			->when($parentFolderId === null,
				static fn ($query) => $query->whereNull('parent_folder_id'),
				static fn ($query) => $query->where('parent_folder_id', $parentFolderId)
			)
			->max('sort_order');

		return ((int) $maxSort) + 1;
	}
}
