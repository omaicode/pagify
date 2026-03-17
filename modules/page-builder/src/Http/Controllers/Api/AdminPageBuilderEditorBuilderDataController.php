<?php

namespace Pagify\PageBuilder\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pagify\Core\Http\Controllers\Api\ApiController;
use Pagify\Core\Services\FrontendThemeManagerService;
use Pagify\Core\Services\ThemeHelpers\AssetThemeHelper;
use Pagify\Core\Support\SiteContext;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Services\BlockRegistryService;
use Pagify\PageBuilder\Services\EditorAccessTokenService;

class AdminPageBuilderEditorBuilderDataController extends ApiController
{
	public function __invoke(
		Request $request,
		EditorAccessTokenService $editorAccessToken,
		SiteContext $siteContext,
		BlockRegistryService $blockRegistry,
		FrontendThemeManagerService $themes,
		AssetThemeHelper $assetTheme,
	): JsonResponse {
		$validated = $request->validate([
			'token' => ['required', 'string', 'min:10'],
		]);

		try {
			$claims = $editorAccessToken->verify((string) $validated['token']);
		} catch (\InvalidArgumentException) {
			return $this->error('Invalid editor access token.', 401, 'UNAUTHORIZED');
		}

		$scopes = array_values(array_filter(array_map(
			static fn (mixed $scope): string => is_string($scope) ? trim($scope) : '',
			(array) ($claims['scopes'] ?? []),
		), static fn (string $scope): bool => $scope !== ''));

		if (! in_array('page:read', $scopes, true)) {
			return $this->error('Editor token is missing page:read scope.', 403, 'FORBIDDEN');
		}

		$siteError = $this->validateSiteScope($claims, $siteContext);
		if ($siteError !== null) {
			return $siteError;
		}

		$page = null;
		$pageId = isset($claims['page_id']) ? (int) $claims['page_id'] : null;

		if ($pageId !== null && $pageId > 0) {
			$page = Page::query()->find($pageId);

			if ($page === null) {
				return $this->error('Page not found for editor token.', 404, 'NOT_FOUND');
			}
		}

		$activeTheme = trim((string) ($claims['theme'] ?? ''));
		if ($activeTheme === '') {
			$activeTheme = $themes->activeThemeForCurrentSite();
		}

		$canvasStyles = [
			$assetTheme->url('css/main.css', $activeTheme),
		];

		return $this->success([
			'layout' => (array) ($page?->layout_json ?? []),
			'seo_meta' => (array) ($page?->seo_meta_json ?? []),
			'page' => [
				'id' => $page?->id,
				'title' => $page?->title,
				'slug' => $page?->slug,
				'status' => $page?->status,
			],
			'context' => [
				'active_theme' => $activeTheme,
				'breakpoints' => array_values((array) config('page-builder.breakpoints', ['desktop', 'tablet', 'mobile'])),
				'blocks' => $blockRegistry->all(),
				'canvas_styles' => $canvasStyles,
			],
		]);
	}

	/**
	 * @param array<string, mixed> $claims
	 */
	private function validateSiteScope(array $claims, SiteContext $siteContext): ?JsonResponse
	{
		$claimSiteId = isset($claims['site_id']) ? (int) $claims['site_id'] : null;

		if ($claimSiteId === null || $claimSiteId <= 0) {
			return null;
		}

		$currentSiteId = $siteContext->siteId();
		if ($currentSiteId === null || $currentSiteId !== $claimSiteId) {
			return $this->error('Editor token site scope does not match current site.', 403, 'FORBIDDEN');
		}

		return null;
	}
}
