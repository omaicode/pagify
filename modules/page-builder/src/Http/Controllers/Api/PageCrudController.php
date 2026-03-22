<?php

namespace Pagify\PageBuilder\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pagify\Core\Http\Controllers\Api\ApiController;
use Pagify\Core\Services\AuditLogger;
use Pagify\Core\Support\SiteContext;
use Pagify\PageBuilder\Webstudio\Http\Controllers\Api\Concerns\InteractsWithProjectState;
use Pagify\PageBuilder\Http\Requests\Admin\StorePageRequest;
use Pagify\PageBuilder\Http\Requests\Admin\UpdatePageRequest;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Services\EditorAccessTokenService;
use Pagify\PageBuilder\Services\PageService;

class PageCrudController extends ApiController
{
	use InteractsWithProjectState;

	public function __construct(
		private readonly PageService $pageService,
		private readonly AuditLogger $auditLogger,
	) {
	}

	public function index(Request $request, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'page:read');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		$perPage = max(1, min((int) $request->integer('per_page', 20), 100));
		$pages = Page::query()->latest('id')->paginate($perPage);

		return $this->success(
			$pages->through(fn (Page $page): array => $this->serializePage($page))->toArray(),
			meta: [
				'current_page' => $pages->currentPage(),
				'last_page' => $pages->lastPage(),
				'per_page' => $pages->perPage(),
				'total' => $pages->total(),
			]
		);
	}

	public function store(StorePageRequest $request, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'page:write');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		$adminId = isset($claims['admin_id']) ? (int) $claims['admin_id'] : null;
		$page = $this->pageService->create($request->validated(), $adminId);

		$this->auditLogger->log(
			action: 'page_builder.page.created',
			entityType: Page::class,
			entityId: $page->id,
			metadata: ['slug' => $page->slug],
		);

		return $this->success($this->serializePage($page), 201);
	}

	public function update(UpdatePageRequest $request, Page $page, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'page:write');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		$scopeError = $this->validatePageScope($claims, $page);
		if ($scopeError !== null) {
			return $scopeError;
		}

		$adminId = isset($claims['admin_id']) ? (int) $claims['admin_id'] : null;
		$updated = $this->pageService->update($page, $request->validated(), $adminId);

		$this->auditLogger->log(
			action: 'page_builder.page.updated',
			entityType: Page::class,
			entityId: $updated->id,
			metadata: ['slug' => $updated->slug],
		);

		return $this->success($this->serializePage($updated));
	}

	public function publish(Request $request, Page $page, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'page:publish');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		$scopeError = $this->validatePageScope($claims, $page);
		if ($scopeError !== null) {
			return $scopeError;
		}

		$adminId = isset($claims['admin_id']) ? (int) $claims['admin_id'] : null;
		$published = $this->pageService->publish($page, $adminId);

		$this->auditLogger->log(
			action: 'page_builder.page.published',
			entityType: Page::class,
			entityId: $published->id,
			metadata: ['slug' => $published->slug],
		);

		return $this->success($this->serializePage($published));
	}

	public function destroy(Request $request, Page $page, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'page:write');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		$scopeError = $this->validatePageScope($claims, $page);
		if ($scopeError !== null) {
			return $scopeError;
		}

		$pageId = $page->id;
		$slug = $page->slug;
		$this->pageService->delete($page);

		$this->auditLogger->log(
			action: 'page_builder.page.deleted',
			entityType: Page::class,
			entityId: $pageId,
			metadata: ['slug' => $slug],
		);

		return $this->success([
			'id' => $pageId,
			'deleted' => true,
		]);
	}

	/**
	 * @param array<string, mixed> $claims
	 */
	private function validatePageScope(array $claims, Page $page): ?JsonResponse
	{
		$claimPageId = isset($claims['page_id']) ? (int) $claims['page_id'] : null;

		if ($claimPageId !== null && $claimPageId > 0 && $claimPageId !== (int) $page->id) {
			return response()->json([
				'status' => 'authorization_error',
				'errors' => 'Editor token page scope mismatch.',
			], 403);
		}

		return null;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function serializePage(Page $page): array
	{
		return [
			'id' => $page->id,
			'title' => $page->title,
			'slug' => $page->slug,
			'status' => $page->status,
			'layout' => (array) ($page->layout_json ?? []),
			'seo_meta' => (array) ($page->seo_meta_json ?? []),
			'published_at' => $page->published_at?->toIso8601String(),
			'updated_at' => $page->updated_at?->toIso8601String(),
			'created_at' => $page->created_at?->toIso8601String(),
			'routes' => [
				'preview' => route('page-builder.admin.pages.preview', $page),
			],
		];
	}
}
