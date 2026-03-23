<?php

namespace Pagify\PageBuilder\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Models\PageFolder;
use Pagify\PageBuilder\Repositories\PageFolderRepository;

class PageFolderService
{
	public const ROOT_FOLDER_ID = 'root';

	public function __construct(
		private readonly PageFolderRepository $folderRepository,
	) {
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	public function create(array $payload, ?int $siteId): PageFolder
	{
		return DB::transaction(function () use ($payload, $siteId): PageFolder {
			$folderId = trim((string) ($payload['folder_id'] ?? ''));
			if ($folderId === '') {
				$folderId = 'folder-' . Str::lower(Str::random(10));
			}

			if ($folderId === self::ROOT_FOLDER_ID) {
				throw ValidationException::withMessages([
					'folder_id' => __('Folder id is reserved.'),
				]);
			}

			if ($this->folderRepository->findByFolderId($folderId, $siteId) instanceof PageFolder) {
				throw ValidationException::withMessages([
					'folder_id' => __('Folder id already exists for this site.'),
				]);
			}

			$parentFolderId = $this->normalizeParentFolderId((string) ($payload['parent_folder_id'] ?? ''));
			$this->ensureParentExists($parentFolderId, $siteId);

			$name = trim((string) ($payload['name'] ?? 'Untitled'));
			$slug = trim((string) ($payload['slug'] ?? Str::slug($name)));

			if ($name === '' || $slug === '') {
				throw ValidationException::withMessages([
					'name' => __('Folder name and slug are required.'),
				]);
			}

			return PageFolder::query()->create([
				'site_id' => $siteId,
				'folder_id' => $folderId,
				'name' => $name,
				'slug' => $slug,
				'parent_folder_id' => $parentFolderId,
				'sort_order' => $this->folderRepository->nextSortOrder($siteId, $parentFolderId),
			]);
		});
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	public function update(string $folderId, array $payload, ?int $siteId): PageFolder
	{
		return DB::transaction(function () use ($folderId, $payload, $siteId): PageFolder {
			$folder = $this->requireFolder($folderId, $siteId);

			if (array_key_exists('name', $payload)) {
				$name = trim((string) $payload['name']);
				if ($name === '') {
					throw ValidationException::withMessages(['name' => __('Folder name is required.')]);
				}
				$folder->name = $name;
			}

			if (array_key_exists('slug', $payload)) {
				$slug = trim((string) $payload['slug']);
				if ($slug === '') {
					throw ValidationException::withMessages(['slug' => __('Folder slug is required.')]);
				}
				$folder->slug = $slug;
			}

			if (array_key_exists('parent_folder_id', $payload)) {
				$parentFolderId = $this->normalizeParentFolderId((string) ($payload['parent_folder_id'] ?? ''));
				$this->moveItem('folder', $folderId, $parentFolderId, null, $siteId);
				$folder = $this->requireFolder($folderId, $siteId);
			}

			$folder->save();

			return $folder->refresh();
		});
	}

	public function delete(string $folderId, ?int $siteId): void
	{
		DB::transaction(function () use ($folderId, $siteId): void {
			$folder = $this->requireFolder($folderId, $siteId);

			PageFolder::query()
				->where('site_id', $siteId)
				->where('parent_folder_id', $folder->folder_id)
				->update(['parent_folder_id' => null]);

			Page::query()
				->where('site_id', $siteId)
				->where('folder_id', $folder->folder_id)
				->update(['folder_id' => null]);

			$folder->delete();
		});
	}

	public function moveItem(string $itemType, string $itemId, ?string $parentFolderId, ?int $indexWithinChildren, ?int $siteId): void
	{
		$normalizedParentFolderId = $this->normalizeParentFolderId((string) ($parentFolderId ?? ''));
		$this->ensureParentExists($normalizedParentFolderId, $siteId);

		if ($itemType === 'folder') {
			$this->moveFolder($itemId, $normalizedParentFolderId, $indexWithinChildren, $siteId);

			return;
		}

		if ($itemType === 'page') {
			$this->movePage($itemId, $normalizedParentFolderId, $indexWithinChildren, $siteId);

			return;
		}

		throw ValidationException::withMessages([
			'item_type' => __('Unsupported move item type.'),
		]);
	}

	/**
	 * @param Collection<int, Page> $allPages
	 * @return array{pages: array<string, mixed>, instances: array<int, array<string, mixed>>}
	 */
	public function buildPagesBundle(string $projectId, ?Page $selectedPage, Collection $allPages, ?int $siteId): array
	{
		$homePage = $allPages->first(static fn (mixed $item): bool => $item instanceof Page && (bool) $item->is_home)
			?? $selectedPage
			?? $allPages->first();

		if (! $homePage instanceof Page) {
			$homePageId = 'home-' . $projectId;
			$homeRootInstanceId = 'root-' . $homePageId;

			return [
				'pages' => [
					'homePage' => [
						'id' => $homePageId,
						'name' => 'Home',
						'path' => '',
						'title' => '"Home"',
						'meta' => [],
						'rootInstanceId' => $homeRootInstanceId,
					],
					'pages' => [],
					'folders' => [[
						'id' => self::ROOT_FOLDER_ID,
						'name' => 'Root',
						'slug' => '',
						'children' => [$homePageId],
					]],
				],
				'instances' => [[
					'type' => 'instance',
					'id' => $homeRootInstanceId,
					'component' => 'ws:element',
					'children' => [],
					'tag' => 'body',
				]],
			];
		}

		$pageItems = [];
		$pageMap = [];
		$instances = [];

		foreach ($allPages as $page) {
			if (! $page instanceof Page) {
				continue;
			}

			$pageId = (string) $page->id;
			$rootInstanceId = 'root-' . $pageId;
			$seo = (array) ($page->seo_meta_json ?? []);
			$title = (string) ($seo['title'] ?? $page->title);
			$quotedTitle = json_encode($title, JSON_UNESCAPED_SLASHES);
			if (! is_string($quotedTitle) || $quotedTitle === '') {
				$quotedTitle = '"' . addslashes($title) . '"';
			}

			$slug = trim((string) $page->slug, '/');
			$path = $page->id === $homePage->id ? '' : '/' . $slug;

			$pageItems[] = [
				'id' => $pageId,
				'name' => (string) $page->title,
				'path' => $path,
				'title' => $quotedTitle,
				'meta' => [],
				'rootInstanceId' => $rootInstanceId,
			];

			$pageMap[$pageId] = $page;

			$instances[] = [
				'type' => 'instance',
				'id' => $rootInstanceId,
				'component' => 'ws:element',
				'children' => [],
				'tag' => 'body',
			];
		}

		$folders = $this->folderRepository->listBySite($siteId);
		$folderNodes = [[
			'id' => self::ROOT_FOLDER_ID,
			'name' => 'Root',
			'slug' => '',
			'children' => [],
		]];

		$folderChildren = [];
		foreach ($folders as $folder) {
			$folderChildren[$folder->folder_id] = [];
			$folderNodes[] = [
				'id' => (string) $folder->folder_id,
				'name' => (string) $folder->name,
				'slug' => (string) $folder->slug,
				'children' => [],
			];
		}

		$rootFolders = [];
		foreach ($folders as $folder) {
			$parentId = $folder->parent_folder_id;
			$folderId = (string) $folder->folder_id;
			$folderEntry = [
				'id' => $folderId,
				'order' => (int) $folder->sort_order,
				'label' => (string) $folder->name,
				'type' => 'folder',
				'is_home' => false,
			];

			if ($parentId === null || $parentId === '') {
				$rootFolders[] = $folderEntry;
				continue;
			}

			if (! array_key_exists($parentId, $folderChildren)) {
				$rootFolders[] = $folderEntry;
				continue;
			}

			$folderChildren[$parentId][] = $folderEntry;
		}

		$rootPages = [];
		$pagesByFolder = [];
		foreach ($allPages->sortBy('folder_order')->values() as $page) {
			if (! $page instanceof Page) {
				continue;
			}

			$pageId = (string) $page->id;
			$pageEntry = [
				'id' => $pageId,
				'order' => (int) $page->folder_order,
				'label' => (string) $page->title,
				'type' => 'page',
				'is_home' => (bool) $page->is_home || $pageId === (string) $homePage->id,
			];

			$folderId = trim((string) ($page->folder_id ?? ''));
			if ($folderId === '' || ! array_key_exists($folderId, $folderChildren)) {
				$rootPages[] = $pageEntry;
				continue;
			}

			$pagesByFolder[$folderId] ??= [];
			$pagesByFolder[$folderId][] = $pageEntry;
		}

		$normalizedFolderNodes = [];
		foreach ($folderNodes as $folderNode) {
			$folderId = (string) $folderNode['id'];
			if ($folderId === self::ROOT_FOLDER_ID) {
				$folderNode['children'] = $this->normalizeOrderedChildren(array_merge($rootFolders, $rootPages));
			} else {
				$children = array_merge($folderChildren[$folderId] ?? [], $pagesByFolder[$folderId] ?? []);
				$folderNode['children'] = $this->normalizeOrderedChildren($children);
			}

			$normalizedFolderNodes[] = $folderNode;
		}

		$homeSeo = (array) ($homePage->seo_meta_json ?? []);
		$homeTitle = (string) ($homeSeo['title'] ?? $homePage->title);
		$quotedHomeTitle = json_encode($homeTitle, JSON_UNESCAPED_SLASHES);
		if (! is_string($quotedHomeTitle) || $quotedHomeTitle === '') {
			$quotedHomeTitle = '"' . addslashes($homeTitle) . '"';
		}

		return [
			'pages' => [
				'homePage' => [
					'id' => (string) $homePage->id,
					'name' => (string) $homePage->title,
					'path' => '',
					'title' => $quotedHomeTitle,
					'meta' => [],
					'rootInstanceId' => 'root-' . (string) $homePage->id,
				],
				'pages' => array_values(array_filter($pageItems, static fn (array $item): bool => $item['id'] !== (string) $homePage->id)),
				'folders' => $normalizedFolderNodes,
			],
			'instances' => $instances,
		];
	}

	/**
	 * @param array<int, array{id: string, order: int, label: string, type: string, is_home: bool}> $children
	 * @return array<int, string>
	 */
	private function normalizeOrderedChildren(array $children): array
	{
		usort($children, static function (array $left, array $right): int {
			$orderCompare = ((int) $left['order']) <=> ((int) $right['order']);
			if ($orderCompare !== 0) {
				return $orderCompare;
			}

			if (($left['is_home'] ?? false) !== ($right['is_home'] ?? false)) {
				return ($left['is_home'] ?? false) ? -1 : 1;
			}

			$labelCompare = strnatcasecmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
			if ($labelCompare !== 0) {
				return $labelCompare;
			}

			if (($left['type'] ?? '') !== ($right['type'] ?? '')) {
				return ($left['type'] ?? '') <=> ($right['type'] ?? '');
			}

			return strcmp((string) ($left['id'] ?? ''), (string) ($right['id'] ?? ''));
		});

		$orderedIds = [];
		foreach ($children as $child) {
			$childId = (string) ($child['id'] ?? '');
			if ($childId === '' || in_array($childId, $orderedIds, true)) {
				continue;
			}
			$orderedIds[] = $childId;
		}

		return $orderedIds;
	}

	private function moveFolder(string $folderId, ?string $parentFolderId, ?int $indexWithinChildren, ?int $siteId): void
	{
		DB::transaction(function () use ($folderId, $parentFolderId, $indexWithinChildren, $siteId): void {
			$folder = $this->requireFolder($folderId, $siteId);

			if ($parentFolderId === $folder->folder_id) {
				throw ValidationException::withMessages([
					'parent_folder_id' => __('Folder cannot be moved into itself.'),
				]);
			}

			if ($parentFolderId !== null && $this->isDescendantFolder($parentFolderId, $folder->folder_id, $siteId)) {
				throw ValidationException::withMessages([
					'parent_folder_id' => __('Folder cannot be moved into its own child.'),
				]);
			}

			$folder->parent_folder_id = $parentFolderId;
			$folder->sort_order = 0;
			$folder->save();

			$this->reindexFolderSiblings($parentFolderId, $siteId, $folder->folder_id, $indexWithinChildren);
		});
	}

	private function movePage(string $pageId, ?string $parentFolderId, ?int $indexWithinChildren, ?int $siteId): void
	{
		DB::transaction(function () use ($pageId, $parentFolderId, $indexWithinChildren, $siteId): void {
			$page = Page::query()
				->where('site_id', $siteId)
				->whereKey((int) $pageId)
				->first();

			if (! $page instanceof Page) {
				throw ValidationException::withMessages([
					'item_id' => __('Page not found for current site.'),
				]);
			}

			$page->folder_id = $parentFolderId;
			$page->folder_order = 0;
			$page->save();

			$this->reindexPageSiblings($parentFolderId, $siteId, (string) $page->id, $indexWithinChildren);
		});
	}

	private function reindexFolderSiblings(?string $parentFolderId, ?int $siteId, string $movedFolderId, ?int $targetIndex): void
	{
		$siblings = PageFolder::query()
			->where('site_id', $siteId)
			->when($parentFolderId === null,
				static fn ($query) => $query->whereNull('parent_folder_id'),
				static fn ($query) => $query->where('parent_folder_id', $parentFolderId)
			)
			->orderBy('sort_order')
			->orderBy('id')
			->get();

		$orderedIds = $siblings
			->pluck('folder_id')
			->filter(static fn (mixed $value): bool => is_string($value) && $value !== $movedFolderId)
			->values()
			->all();

		$safeIndex = $targetIndex === null
			? count($orderedIds)
			: max(0, min($targetIndex, count($orderedIds)));

		array_splice($orderedIds, $safeIndex, 0, [$movedFolderId]);

		foreach ($orderedIds as $index => $folderId) {
			PageFolder::query()
				->where('site_id', $siteId)
				->where('folder_id', $folderId)
				->update(['sort_order' => $index]);
		}
	}

	private function reindexPageSiblings(?string $parentFolderId, ?int $siteId, string $movedPageId, ?int $targetIndex): void
	{
		$siblings = Page::query()
			->where('site_id', $siteId)
			->when($parentFolderId === null,
				static fn ($query) => $query->whereNull('folder_id'),
				static fn ($query) => $query->where('folder_id', $parentFolderId)
			)
			->orderBy('folder_order')
			->orderBy('id')
			->get();

		$orderedIds = $siblings
			->pluck('id')
			->map(static fn (mixed $id): string => (string) $id)
			->filter(static fn (string $id): bool => $id !== $movedPageId)
			->values()
			->all();

		$safeIndex = $targetIndex === null
			? count($orderedIds)
			: max(0, min($targetIndex, count($orderedIds)));

		array_splice($orderedIds, $safeIndex, 0, [$movedPageId]);

		foreach ($orderedIds as $index => $id) {
			Page::query()
				->where('site_id', $siteId)
				->whereKey((int) $id)
				->update(['folder_order' => $index]);
		}
	}

	private function ensureParentExists(?string $parentFolderId, ?int $siteId): void
	{
		if ($parentFolderId === null) {
			return;
		}

		if (! $this->folderRepository->findByFolderId($parentFolderId, $siteId) instanceof PageFolder) {
			throw ValidationException::withMessages([
				'parent_folder_id' => __('Parent folder does not exist for this site.'),
			]);
		}
	}

	private function requireFolder(string $folderId, ?int $siteId): PageFolder
	{
		$folder = $this->folderRepository->findByFolderId($folderId, $siteId);

		if (! $folder instanceof PageFolder) {
			throw ValidationException::withMessages([
				'folder_id' => __('Folder not found for current site.'),
			]);
		}

		return $folder;
	}

	private function normalizeParentFolderId(string $folderId): ?string
	{
		$normalized = trim($folderId);
		if ($normalized === '' || $normalized === self::ROOT_FOLDER_ID) {
			return null;
		}

		return $normalized;
	}

	private function isDescendantFolder(string $candidateFolderId, string $ancestorFolderId, ?int $siteId): bool
	{
		$currentId = $candidateFolderId;

		while ($currentId !== '') {
			if ($currentId === $ancestorFolderId) {
				return true;
			}

			$folder = $this->folderRepository->findByFolderId($currentId, $siteId);
			if (! $folder instanceof PageFolder) {
				return false;
			}

			$currentId = (string) ($folder->parent_folder_id ?? '');
		}

		return false;
	}
}
