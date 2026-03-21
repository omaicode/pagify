<?php

namespace Pagify\PageBuilder\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pagify\Core\Http\Controllers\Api\ApiController;
use Pagify\Core\Support\SiteContext;
use Pagify\PageBuilder\Http\Controllers\Api\Concerns\InteractsWithWebstudioProjectState;
use Pagify\PageBuilder\Http\Requests\Admin\MovePageFolderItemRequest;
use Pagify\PageBuilder\Http\Requests\Admin\StorePageFolderRequest;
use Pagify\PageBuilder\Http\Requests\Admin\UpdatePageFolderRequest;
use Pagify\PageBuilder\Services\EditorAccessTokenService;
use Pagify\PageBuilder\Services\PageFolderService;

class PageFolderController extends ApiController
{
	use InteractsWithWebstudioProjectState;

	public function __construct(
		private readonly PageFolderService $pageFolderService,
	) {
	}

	public function index(Request $request, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'page:read');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		$siteId = isset($claims['site_id']) ? (int) $claims['site_id'] : null;
		$bundle = $this->pageFolderService->buildPagesBundle(
			projectId: (string) ($claims['page_id'] ?? 'unified'),
			selectedPage: null,
			allPages: \Pagify\PageBuilder\Models\Page::query()->orderBy('folder_order')->orderBy('id')->get(),
			siteId: $siteId,
		);

		return $this->success([
			'folders' => $bundle['pages']['folders'] ?? [],
		]);
	}

	public function store(StorePageFolderRequest $request, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'page:write');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		$siteId = isset($claims['site_id']) ? (int) $claims['site_id'] : null;
		$folder = $this->pageFolderService->create($request->validated(), $siteId);

		return $this->success([
			'id' => (string) $folder->folder_id,
			'name' => (string) $folder->name,
			'slug' => (string) $folder->slug,
			'parent_folder_id' => (string) ($folder->parent_folder_id ?? PageFolderService::ROOT_FOLDER_ID),
		], 201);
	}

	public function update(UpdatePageFolderRequest $request, string $folderId, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'page:write');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		$siteId = isset($claims['site_id']) ? (int) $claims['site_id'] : null;
		$folder = $this->pageFolderService->update($folderId, $request->validated(), $siteId);

		return $this->success([
			'id' => (string) $folder->folder_id,
			'name' => (string) $folder->name,
			'slug' => (string) $folder->slug,
			'parent_folder_id' => (string) ($folder->parent_folder_id ?? PageFolderService::ROOT_FOLDER_ID),
		]);
	}

	public function destroy(Request $request, string $folderId, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'page:write');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		$siteId = isset($claims['site_id']) ? (int) $claims['site_id'] : null;
		$this->pageFolderService->delete($folderId, $siteId);

		return $this->success([
			'id' => $folderId,
			'deleted' => true,
		]);
	}

	public function move(MovePageFolderItemRequest $request, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'page:write');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		$siteId = isset($claims['site_id']) ? (int) $claims['site_id'] : null;
		$validated = $request->validated();

		$this->pageFolderService->moveItem(
			itemType: (string) $validated['item_type'],
			itemId: (string) $validated['item_id'],
			parentFolderId: isset($validated['parent_folder_id']) ? (string) $validated['parent_folder_id'] : null,
			indexWithinChildren: isset($validated['index_within_children']) ? (int) $validated['index_within_children'] : null,
			siteId: $siteId,
		);

		return $this->success(['status' => 'ok']);
	}
}
