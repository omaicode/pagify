<?php

namespace Pagify\PageBuilder\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pagify\Core\Http\Controllers\Api\ApiController;
use Pagify\Core\Support\SiteContext;
use Pagify\PageBuilder\Webstudio\Http\Controllers\Api\Concerns\InteractsWithProjectState;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Services\EditorAccessTokenService;

class EditorTokenController extends ApiController
{
	use InteractsWithProjectState;

	public function __invoke(
		Request $request,
		SiteContext $siteContext,
		EditorAccessTokenService $editorAccessToken,
	): JsonResponse {
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'page:read');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		$validated = $request->validate([
			'page_id' => ['nullable', 'integer', 'exists:pb_pages,id'],
			'theme' => ['nullable', 'string', 'max:120'],
		]);

		$pageId = isset($validated['page_id']) ? (int) $validated['page_id'] : null;
		$claimPageId = isset($claims['page_id']) ? (int) $claims['page_id'] : null;

		if ($claimPageId !== null && $claimPageId > 0 && $pageId !== null && $pageId !== $claimPageId) {
			return $this->error('Editor token page scope mismatch.', 403, 'FORBIDDEN');
		}

		if ($pageId === null && $claimPageId !== null && $claimPageId > 0) {
			$pageId = $claimPageId;
		}

		$page = null;

		if ($pageId !== null) {
			$page = Page::query()->findOrFail($pageId);
		}

		$site = $siteContext->site();
		$scopes = array_values(array_filter(array_map(
			static fn (mixed $scope): string => is_string($scope) ? trim($scope) : '',
			(array) ($claims['scopes'] ?? [])
		), static fn (string $scope): bool => $scope !== ''));

		if ($scopes === []) {
			$scopes = ['page:read'];
		}

		$token = $editorAccessToken->issue([
			'admin_id' => isset($claims['admin_id']) ? (int) $claims['admin_id'] : null,
			'page_id' => $page?->id,
			'page_slug' => $page?->slug,
			'site_id' => isset($claims['site_id']) ? (int) $claims['site_id'] : $site?->id,
			'site_slug' => isset($claims['site_slug']) ? (string) $claims['site_slug'] : $site?->slug,
			'theme' => (string) ($validated['theme'] ?? (string) ($claims['theme'] ?? '')),
			'scopes' => $scopes,
		]);

		return $this->success([
			'access_token' => $token['token'],
			'expires_at' => $token['expires_at'],
		]);
	}
}
