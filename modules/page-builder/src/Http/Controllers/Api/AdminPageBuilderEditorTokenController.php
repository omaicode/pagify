<?php

namespace Pagify\PageBuilder\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pagify\Core\Http\Controllers\Api\ApiController;
use Pagify\Core\Support\SiteContext;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Services\EditorAccessTokenService;

class AdminPageBuilderEditorTokenController extends ApiController
{
	public function __invoke(
		Request $request,
		SiteContext $siteContext,
		EditorAccessTokenService $editorAccessToken,
	): JsonResponse {
		$validated = $request->validate([
			'page_id' => ['nullable', 'integer', 'exists:pb_pages,id'],
			'theme' => ['nullable', 'string', 'max:120'],
		]);

		$pageId = $validated['page_id'] ?? null;
		$page = null;

		if ($pageId !== null) {
			$page = Page::query()->findOrFail((int) $pageId);
			$this->authorize('update', $page);
		} else {
			$this->authorize('create', Page::class);
		}

		/** @var \Pagify\Core\Models\Admin|null $admin */
		$admin = $request->user('web');
		$site = $siteContext->site();

		$token = $editorAccessToken->issue([
			'admin_id' => $admin?->id,
			'page_id' => $page?->id,
			'page_slug' => $page?->slug,
			'site_id' => $site?->id,
			'site_slug' => $site?->slug,
			'theme' => (string) ($validated['theme'] ?? ''),
			'scopes' => ['page:read', 'page:write', 'page:publish', 'media:read', 'media:write'],
		]);

		return $this->success([
			'access_token' => $token['token'],
			'expires_at' => $token['expires_at'],
		]);
	}
}
