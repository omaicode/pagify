<?php

namespace Pagify\PageBuilder\Http\Controllers\Api\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Pagify\Core\Support\SiteContext;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Models\PageBuilderState;
use Pagify\PageBuilder\Services\EditorAccessTokenService;

trait InteractsWithWebstudioProjectState
{
	/**
	 * @return array<string, mixed>|JsonResponse
	 */
	protected function authorizeRequest(Request $request, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext, string $requiredScope): array|JsonResponse
	{
		$token = $this->extractToken($request);

		if ($token === '') {
			return response()->json([
				'status' => 'authorization_error',
				'errors' => 'Missing x-auth-token header.',
			], 401);
		}

		try {
			$claims = $editorAccessToken->verify($token);
		} catch (\InvalidArgumentException) {
			return response()->json([
				'status' => 'authorization_error',
				'errors' => 'Invalid editor access token.',
			], 401);
		}

		if (! $this->hasScope($claims, $requiredScope)) {
			return response()->json([
				'status' => 'authorization_error',
				'errors' => 'Editor token is missing required scope: ' . $requiredScope,
			], 403);
		}

		$claimSiteId = isset($claims['site_id']) ? (int) $claims['site_id'] : null;
		if ($claimSiteId !== null && $claimSiteId > 0) {
			$currentSiteId = $siteContext->siteId();
			if ($currentSiteId === null || $currentSiteId !== $claimSiteId) {
				return response()->json([
					'status' => 'authorization_error',
					'errors' => 'Editor token site scope mismatch.',
				], 403);
			}
		}

		return $claims;
	}

	protected function extractToken(Request $request): string
	{
		$headerToken = trim((string) $request->header('x-auth-token', ''));
		if ($headerToken !== '') {
			return $headerToken;
		}

		$tokenInput = trim((string) $request->input('token', ''));
		if ($tokenInput !== '') {
			return $tokenInput;
		}

		$accessToken = trim((string) $request->query('accessToken', ''));

		return $accessToken;
	}

	/**
	 * @param array<string, mixed> $claims
	 */
	protected function hasScope(array $claims, string $requiredScope): bool
	{
		$scopes = array_values(array_filter(array_map(
			static fn (mixed $scope): string => is_string($scope) ? trim($scope) : '',
			(array) ($claims['scopes'] ?? []),
		), static fn (string $scope): bool => $scope !== ''));

		return in_array($requiredScope, $scopes, true);
	}

	/**
	 * @param array<string, mixed> $claims
	 */
	protected function resolvePage(array $claims, string $projectId): ?Page
	{
		$pageId = is_numeric($projectId) ? (int) $projectId : null;
		if ($pageId === null || $pageId <= 0) {
			$pageId = isset($claims['page_id']) ? (int) $claims['page_id'] : null;
		}

		if ($pageId === null || $pageId <= 0) {
			return null;
		}

		$page = Page::query()->withTrashed()->find($pageId);
		if (! $page instanceof Page) {
			$page = Page::query()->withoutGlobalScopes()->withTrashed()->find($pageId);
		}

		if (! $page instanceof Page) {
			return null;
		}

		$claimSiteId = isset($claims['site_id']) ? (int) $claims['site_id'] : null;
		if ($claimSiteId !== null && $claimSiteId > 0 && (int) $page->site_id !== $claimSiteId) {
			return null;
		}

		return $page;
	}

	protected function currentVersion(string $projectId, ?Page $page): int
	{
		$cached = Cache::get($this->versionCacheKey($projectId));
		$cachedVersion = is_int($cached) && $cached > 0 ? $cached : null;

		$persistedVersion = null;
		if ($page !== null) {
			$rawPersistedVersion = PageBuilderState::query()
				->where('page_id', $page->id)
				->value('version');

			if (is_int($rawPersistedVersion) && $rawPersistedVersion > 0) {
				$persistedVersion = $rawPersistedVersion;
			} elseif (is_numeric($rawPersistedVersion)) {
				$normalizedPersistedVersion = (int) $rawPersistedVersion;
				if ($normalizedPersistedVersion > 0) {
					$persistedVersion = $normalizedPersistedVersion;
				}
			}
		}

		$resolvedVersion = null;
		if ($cachedVersion !== null && $persistedVersion !== null) {
			$resolvedVersion = max($cachedVersion, $persistedVersion);
		} else {
			$resolvedVersion = $cachedVersion ?? $persistedVersion;
		}

		if (is_int($resolvedVersion) && $resolvedVersion > 0) {
			Cache::put($this->versionCacheKey($projectId), $resolvedVersion, now()->addDay());

			return $resolvedVersion;
		}

		$seed = $page?->updated_at?->timestamp;
		if (! is_int($seed) || $seed <= 0) {
			$seed = 1;
		}

		Cache::put($this->versionCacheKey($projectId), $seed, now()->addDay());

		return $seed;
	}

	protected function versionCacheKey(string $projectId): string
	{
		return 'page-builder:webstudio:version:' . $projectId;
	}

	/**
	 * @param array<string, mixed> $state
	 */
	protected function incrementPublishCount(array &$state): void
	{
		$state['userPublishCount'] = (int) ($state['userPublishCount'] ?? 0) + 1;
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function readProjectRuntimeState(string $projectId, ?Page $page, SiteContext $siteContext): array
	{
		$cached = Cache::get($this->projectStateCacheKey($projectId));
		if (is_array($cached)) {
			return $cached;
		}

		$siteDomain = (string) ($siteContext->site()?->domain ?? 'localhost');
		$defaultDomain = (string) ($page?->slug ?: ('page-' . $projectId));

		$state = [
			'domain' => $defaultDomain,
			'publisherHost' => $siteDomain,
			'domainsVirtual' => [],
			'latestBuildVirtual' => null,
			'latestStaticBuild' => null,
			'publishedBuilds' => [],
			'userPublishCount' => 0,
			'marketplaceApprovalStatus' => 'UNLISTED',
		];

		$this->writeProjectRuntimeState($projectId, $state);

		return $state;
	}

	/**
	 * @param array<string, mixed> $state
	 */
	protected function writeProjectRuntimeState(string $projectId, array $state): void
	{
		Cache::put($this->projectStateCacheKey($projectId), $state, now()->addDay());
	}

	protected function projectStateCacheKey(string $projectId): string
	{
		return 'page-builder:webstudio:project-state:' . $projectId;
	}

	/**
	 * @param array<string, mixed> $state
	 * @return array<string, mixed>
	 */
	protected function projectPayload(string $projectId, Page $page, array $state): array
	{
		$createdAtIso = $page->created_at?->toIso8601String() ?? now()->toIso8601String();

		return [
			'id' => $projectId,
			'title' => (string) $page->title,
			'domain' => (string) ($state['domain'] ?? $page->slug),
			'isDeleted' => false,
			'createdAt' => $createdAtIso,
			'previewImageAssetId' => null,
			'domainsVirtual' => array_values((array) ($state['domainsVirtual'] ?? [])),
			'latestBuildVirtual' => $state['latestBuildVirtual'] ?? null,
			'latestStaticBuild' => $state['latestStaticBuild'] ?? null,
			'marketplaceApprovalStatus' => (string) ($state['marketplaceApprovalStatus'] ?? 'UNLISTED'),
			'tags' => null,
			'previewImageAsset' => null,
			'userId' => isset($page->created_by_admin_id) ? (string) $page->created_by_admin_id : null,
		];
	}
}
