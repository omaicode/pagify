<?php

namespace Pagify\PageBuilder\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pagify\Core\Http\Controllers\Api\ApiController;
use Pagify\Core\Support\SiteContext;
use Pagify\Media\Http\Resources\MediaAssetResource;
use Pagify\Media\Models\MediaAsset;
use Pagify\Media\Models\MediaFolder;
use Pagify\Media\Services\MediaAssetManager;
use Pagify\PageBuilder\Services\EditorAccessTokenService;

class AdminPageBuilderEditorMediaController extends ApiController
{
	public function index(Request $request, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext): JsonResponse
	{
		$validated = $request->validate([
			'token' => ['required', 'string', 'min:10'],
			'q' => ['nullable', 'string', 'max:120'],
			'kind' => ['nullable', 'string', 'max:60'],
			'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
		]);

		$claims = $this->verifyToken($editorAccessToken, (string) $validated['token']);
		if ($claims === null) {
			return $this->error('Invalid editor access token.', 401, 'UNAUTHORIZED');
		}

		if (! $this->hasScope($claims, 'media:read')) {
			return $this->error('Editor token is missing media:read scope.', 403, 'FORBIDDEN');
		}

		$siteError = $this->validateSiteScope($claims, $siteContext);
		if ($siteError !== null) {
			return $siteError;
		}

		$perPage = (int) ($validated['per_page'] ?? 24);
		$query = MediaAsset::query()->with(['folder', 'transforms'])->withCount('usages');

		if (! empty($validated['q'])) {
			$keyword = '%' . (string) $validated['q'] . '%';
			$query->where(static function ($builder) use ($keyword): void {
				$builder
					->where('filename', 'like', $keyword)
					->orWhere('original_name', 'like', $keyword)
					->orWhere('mime_type', 'like', $keyword);
			});
		}

		if (! empty($validated['kind'])) {
			$query->where('kind', (string) $validated['kind']);
		}

		$assets = $query->orderByDesc('created_at')->paginate($perPage);

		return $this->success(MediaAssetResource::collection($assets)->resolve(), meta: [
			'pagination' => [
				'current_page' => $assets->currentPage(),
				'per_page' => $assets->perPage(),
				'total' => $assets->total(),
				'last_page' => $assets->lastPage(),
			],
		]);
	}

	public function upload(Request $request, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext, MediaAssetManager $assetManager): JsonResponse
	{
		$validated = $request->validate([
			'token' => ['required', 'string', 'min:10'],
			'file' => ['required', 'file', 'max:51200'],
			'folder_id' => ['nullable', 'integer', 'exists:media_folders,id'],
			'alt_text' => ['nullable', 'string', 'max:255'],
			'caption' => ['nullable', 'string', 'max:255'],
		]);

		$claims = $this->verifyToken($editorAccessToken, (string) $validated['token']);
		if ($claims === null) {
			return $this->error('Invalid editor access token.', 401, 'UNAUTHORIZED');
		}

		if (! $this->hasScope($claims, 'media:write')) {
			return $this->error('Editor token is missing media:write scope.', 403, 'FORBIDDEN');
		}

		$siteError = $this->validateSiteScope($claims, $siteContext);
		if ($siteError !== null) {
			return $siteError;
		}

		$folder = null;
		if (! empty($validated['folder_id'])) {
			$folder = MediaFolder::query()->find((int) $validated['folder_id']);
		}

		$siteId = isset($claims['site_id']) ? (int) $claims['site_id'] : null;
		$adminId = isset($claims['admin_id']) ? (int) $claims['admin_id'] : null;

		$asset = $assetManager->upload(
			file: $request->file('file'),
			folder: $folder,
			siteId: $siteId,
			uploadedByAdminId: $adminId,
			altText: isset($validated['alt_text']) ? (string) $validated['alt_text'] : null,
			caption: isset($validated['caption']) ? (string) $validated['caption'] : null,
		);

		$asset->load(['folder', 'transforms'])->loadCount('usages');

		return $this->success((new MediaAssetResource($asset))->resolve(), 201);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function verifyToken(EditorAccessTokenService $editorAccessToken, string $token): ?array
	{
		try {
			return $editorAccessToken->verify($token);
		} catch (\InvalidArgumentException) {
			return null;
		}
	}

	/**
	 * @param array<string, mixed> $claims
	 */
	private function hasScope(array $claims, string $requiredScope): bool
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
