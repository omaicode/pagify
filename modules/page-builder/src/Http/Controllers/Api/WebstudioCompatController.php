<?php

namespace Pagify\PageBuilder\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Pagify\Core\Http\Controllers\Api\ApiController;
use Pagify\Core\Support\SiteContext;
use Pagify\Media\Models\MediaAsset;
use Pagify\Media\Services\MediaAssetManager;
use Pagify\PageBuilder\Http\Controllers\Api\Concerns\InteractsWithWebstudioProjectState;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Models\PageBuilderState;
use Pagify\PageBuilder\Services\EditorAccessTokenService;

class WebstudioCompatController extends ApiController
{
	use InteractsWithWebstudioProjectState;

	public function dashboardLogout(Request $request): JsonResponse
	{
		if (Auth::guard('web')->check()) {
			Auth::guard('web')->logout();
		}

		$adminPrefix = trim((string) config('app.admin_url_prefix', 'admin'), '/');

		return response()->json([
			'redirectTo' => url('/'.$adminPrefix.'/login'),
		]);
	}

	public function data(Request $request, string $projectId, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'page:read');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		$this->ensureInitialPageExists($siteContext, $claims);

		$page = $this->resolvePage($claims, $projectId);
		$builderState = $page !== null
			? PageBuilderState::query()->where('page_id', $page->id)->first()
			: null;
		$version = $builderState?->version ?? $this->currentVersion($projectId, $page);
		$state = $this->readProjectRuntimeState($projectId, $page, $siteContext);
		$allPages = Page::query()->orderBy('id')->get();
		$pageBundle = $this->buildPagesBundle($projectId, $page, $allPages);
		$persistedBuildData = is_array($builderState?->data_json)
			? (array) $builderState->data_json
			: [];
		$nowIso = now()->toIso8601String();

		$response = [
			'id' => (string) ($builderState?->build_id ?: ('build-'.$projectId)),
			'version' => $version,
			'projectId' => (string) $projectId,
			'createdAt' => $nowIso,
			'updatedAt' => $nowIso,
			'project' => $page !== null
			? $this->projectPayload($page, $state)
			: [
				'id' => (string) $projectId,
				'title' => 'Pagify Project',
				'domain' => (string) ($state['domain'] ?? ('project-'.$projectId)),
				'userId' => isset($claims['admin_id']) ? (string) $claims['admin_id'] : null,
				'isDeleted' => false,
				'createdAt' => $nowIso,
				'previewImageAssetId' => null,
				'marketplaceApprovalStatus' => (string) ($state['marketplaceApprovalStatus'] ?? 'UNLISTED'),
				'tags' => null,
				'previewImageAsset' => null,
				'latestBuildVirtual' => $state['latestBuildVirtual'] ?? null,
				'domainsVirtual' => array_values((array) ($state['domainsVirtual'] ?? [])),
				'latestStaticBuild' => $state['latestStaticBuild'] ?? null,
			],
			'assets' => $this->mapAssetsForProject($projectId),
			'instances' => $this->persistedOrDefaultArray($persistedBuildData, 'instances', $pageBundle['instances']),
			'props' => $this->persistedOrDefaultArray($persistedBuildData, 'props', []),
			'dataSources' => $this->persistedOrDefaultArray($persistedBuildData, 'dataSources', []),
			'resources' => $this->persistedOrDefaultArray($persistedBuildData, 'resources', []),
			'breakpoints' => $this->persistedOrDefaultArray($persistedBuildData, 'breakpoints', [
				['id' => 'aqVX0VTx9bxHqRd0MS3a3', 'label' => 'Base'],
				['id' => 'wS1by9VwuSNP-NjL5yM7k', 'label' => 'Tablet', 'maxWidth' => 991],
				['id' => 'KlGACE3pdP2LWNWyLD8dL', 'label' => 'Mobile landscape', 'maxWidth' => 767],
				['id' => 'SH8TDdiQpG6IF3gDM0ZJU', 'label' => 'Mobile portrait', 'maxWidth' => 479],
			]),
			'styleSources' => $this->persistedOrDefaultArray($persistedBuildData, 'styleSources', []),
			'styleSourceSelections' => $this->persistedOrDefaultArray($persistedBuildData, 'styleSourceSelections', []),
			'styles' => $this->persistedOrDefaultArray($persistedBuildData, 'styles', []),
			'marketplaceProduct' => (object) [],
			// Page tree metadata should stay authoritative from Pagify DB/API,
			// not from persisted builder snapshots, so publish status/title/slug
			// always reflect the latest server state when switching pages.
			'pages' => $pageBundle['pages'],
			'publisherHost' => (string) ($state['publisherHost'] ?? parse_url($request->getSchemeAndHttpHost(), PHP_URL_HOST) ?: 'localhost'),
		];

		return response()->json($response);
	}

	/**
	 * @param array<string, mixed> $claims
	 */
	private function ensureInitialPageExists(SiteContext $siteContext, array $claims): void
	{
		if (Page::query()->exists()) {
			return;
		}

		$site = $siteContext->site();
		$adminId = isset($claims['admin_id']) && is_numeric($claims['admin_id'])
			? (int) $claims['admin_id']
			: null;

		Page::query()->create([
			'site_id' => $site?->id,
			'title' => 'Empty',
			'slug' => 'empty',
			'status' => 'draft',
			'layout_json' => [
				'type' => 'webstudio',
				'webstudio' => [
					'html' => '<main data-pbx-content-slot="true"></main>',
					'css' => '',
				],
			],
			'seo_meta_json' => [
				'title' => 'Empty',
			],
			'created_by_admin_id' => $adminId,
			'updated_by_admin_id' => $adminId,
		]);
	}

	/**
	 * @return array{pages: array<string, mixed>, instances: array<int, array<string, mixed>>}
	 */
	private function buildPagesBundle(string $projectId, ?Page $selectedPage, Collection $allPages): array
	{
		$homePage = $selectedPage ?? $allPages->first();

		if (! $homePage instanceof Page) {
			$homePageId = 'home-'.$projectId;
			$homeRootInstanceId = 'root-'.$homePageId;

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
						'id' => 'root',
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

		$items = [];
		$children = [];
		$instances = [];

		foreach ($allPages as $page) {
			if (! $page instanceof Page) {
				continue;
			}

			$pageId = (string) $page->id;
			$rootInstanceId = 'root-'.$pageId;
			$seo = (array) ($page->seo_meta_json ?? []);
			$title = (string) ($seo['title'] ?? $page->title);
			$quotedTitle = json_encode($title, JSON_UNESCAPED_SLASHES);
			if (! is_string($quotedTitle) || $quotedTitle === '') {
				$quotedTitle = '"'.addslashes($title).'"';
			}

			$slug = trim((string) $page->slug, '/');
			$path = $page->id === $homePage->id ? '' : '/'.$slug;

			$items[] = [
				'id' => $pageId,
				'name' => (string) $page->title,
				'path' => $path,
				'title' => $quotedTitle,
				'meta' => [
					'status' => (string) $page->status,
					'published_at' => $page->published_at?->toIso8601String(),
				],
				'rootInstanceId' => $rootInstanceId,
			];

			$children[] = $pageId;
			$instances[] = [
				'type' => 'instance',
				'id' => $rootInstanceId,
				'component' => 'ws:element',
				'children' => [],
				'tag' => 'body',
			];
		}

		$homeSeo = (array) ($homePage->seo_meta_json ?? []);
		$homeTitle = (string) ($homeSeo['title'] ?? $homePage->title);
		$quotedHomeTitle = json_encode($homeTitle, JSON_UNESCAPED_SLASHES);
		if (! is_string($quotedHomeTitle) || $quotedHomeTitle === '') {
			$quotedHomeTitle = '"'.addslashes($homeTitle).'"';
		}

		return [
			'pages' => [
				'homePage' => [
					'id' => (string) $homePage->id,
					'name' => (string) $homePage->title,
					'path' => '',
					'title' => $quotedHomeTitle,
					'meta' => [
						'status' => (string) $homePage->status,
						'published_at' => $homePage->published_at?->toIso8601String(),
					],
					'rootInstanceId' => 'root-'.(string) $homePage->id,
				],
				'pages' => array_values(array_filter($items, static fn (array $item): bool => $item['id'] !== (string) $homePage->id)),
				'folders' => [
					[
						'id' => 'root',
						'name' => 'Root',
						'slug' => '',
						'children' => $children,
					],
				],
			],
			'instances' => $instances,
		];
	}

	public function patch(Request $request, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'page:write');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		$payload = $request->json()->all();
		$projectId = (string) ($payload['projectId'] ?? '');
		$clientVersion = (int) ($payload['version'] ?? 0);

		if ($projectId === '') {
			return response()->json([
				'status' => 'error',
				'errors' => 'Project id required',
			], 400);
		}

		$page = $this->resolvePage($claims, $projectId);
		$serverVersion = $this->currentVersion($projectId, $page);

		if ($clientVersion !== $serverVersion) {
			return response()->json([
				'status' => 'version_mismatched',
				'errors' => 'Builder data version changed. Please reload editor to continue.',
			]);
		}

		$nextVersion = $serverVersion + 1;
		Cache::put($this->versionCacheKey($projectId), $nextVersion, now()->addDay());

		if ($page !== null) {
			$builderState = PageBuilderState::query()->firstOrNew([
				'page_id' => $page->id,
			]);
			$builderState->site_id = $page->site_id;
			$builderState->build_id = (string) ($payload['buildId'] ?? ($builderState->build_id ?: ('build-'.$projectId)));
			$builderState->version = $nextVersion;

			if (isset($payload['state']) && is_array($payload['state'])) {
				$builderState->data_json = (array) $payload['state'];
			}

			$builderState->save();
		}

		return response()->json([
			'status' => 'ok',
		]);
	}

	public function resourcesLoader(Request $request, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'page:read');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		return response()->json([]);
	}

	public function assets(Request $request, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'media:read');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		if ($request->isMethod('get')) {
			$projectId = (string) ($request->query('projectId') ?? $request->query('pageId') ?? (string) ($claims['page_id'] ?? ''));

			return response()->json($this->mapAssetsForProject($projectId));
		}

		$projectId = (string) $request->input('projectId', (string) ($claims['page_id'] ?? ''));
		$filename = trim((string) $request->input('filename', 'asset.bin'));
		$type = trim((string) $request->input('type', 'file'));

		if ($projectId === '' || $filename === '') {
			return response()->json(['errors' => 'projectId and filename are required'], 422);
		}

		$extension = pathinfo($filename, PATHINFO_EXTENSION);
		$normalized = Str::slug(pathinfo($filename, PATHINFO_FILENAME));
		if ($normalized === '') {
			$normalized = 'asset';
		}

		$name = $normalized.'-'.Str::lower($type).'-'.Str::lower(Str::random(8));
		if ($extension !== '') {
			$name .= '.'.strtolower($extension);
		}

		return response()->json(['name' => $name]);
	}

	public function uploadAsset(Request $request, string $name, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext, MediaAssetManager $assetManager): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'media:write');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		$rawBody = $request->getContent();
		$contentType = (string) $request->header('content-type', 'application/octet-stream');
		$detectedMime = str_contains($contentType, ';') ? explode(';', $contentType)[0] : $contentType;

		if (str_contains($contentType, 'application/json')) {
			$payload = $request->json()->all();
			$url = isset($payload['url']) && is_string($payload['url']) ? trim($payload['url']) : '';
			if ($url !== '') {
				$response = rescue(static fn () => file_get_contents($url), null, false);
				if (! is_string($response)) {
					return response()->json(['errors' => 'Unable to fetch remote asset URL'], 422);
				}

				$rawBody = $response;
			}
		}

		if (! is_string($rawBody) || $rawBody === '') {
			return response()->json(['errors' => 'Asset payload is empty'], 422);
		}

		$tmpPath = tempnam(sys_get_temp_dir(), 'pbx-upload-');
		if ($tmpPath === false) {
			return response()->json(['errors' => 'Unable to prepare upload temp file'], 500);
		}

		file_put_contents($tmpPath, $rawBody);

		$uploadedFile = new UploadedFile(
			$tmpPath,
			$name,
			$detectedMime,
			null,
			true
		);

		$siteId = isset($claims['site_id']) ? (int) $claims['site_id'] : null;
		$adminId = isset($claims['admin_id']) ? (int) $claims['admin_id'] : null;

		$asset = $assetManager->upload(
			file: $uploadedFile,
			folder: null,
			siteId: $siteId,
			uploadedByAdminId: $adminId,
			altText: null,
			caption: null,
		);

		return response()->json([
			'uploadedAssets' => [$this->mapAsset($asset, (string) ($claims['page_id'] ?? ''))],
		]);
	}

	public function deleteAsset(Request $request, string $assetId, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext, MediaAssetManager $assetManager): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'media:write');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		$asset = MediaAsset::query()->find($assetId);

		if (! $asset instanceof MediaAsset) {
			return response()->json(['errors' => 'Asset not found'], 404);
		}

		$assetManager->delete($asset);

		return response()->json([
			'deleted' => true,
			'id' => $assetId,
		]);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function mapAssetsForProject(string $projectId): array
	{
		$assets = MediaAsset::query()
			->orderByDesc('created_at')
			->limit(100)
			->get();

		return $assets->map(fn (MediaAsset $asset): array => $this->mapAsset($asset, $projectId))->values()->all();
	}

	/**
	 * @return array<string, mixed>
	 */
	private function mapAsset(MediaAsset $asset, string $projectId): array
	{
		$type = str_starts_with((string) $asset->mime_type, 'image/') ? 'image' : 'file';
		$storageUrl = Storage::disk((string) $asset->disk)->url((string) $asset->path);
		$storagePath = parse_url($storageUrl, PHP_URL_PATH);
		$assetName = is_string($storagePath) && $storagePath !== ''
			? ltrim($storagePath, '/')
			: ltrim((string) $asset->path, '/');

		$mapped = [
			'id' => (string) $asset->id,
			'name' => $assetName,
			'filename' => (string) ($asset->original_name ?: $asset->filename),
			'format' => (string) ($asset->extension ?: pathinfo((string) $asset->filename, PATHINFO_EXTENSION)),
			'type' => $type,
			'description' => (string) ($asset->alt_text ?? ''),
			'createdAt' => $asset->created_at?->toIso8601String() ?? now()->toIso8601String(),
			'projectId' => $projectId,
			'size' => (int) ($asset->size_bytes ?? 0),
			'meta' => [],
		];

		if ($type === 'image') {
			$mapped['meta'] = [
				'width' => (int) ($asset->width ?? 0),
				'height' => (int) ($asset->height ?? 0),
			];
		}

		return $mapped;
	}

	/**
	 * @param array<string, mixed> $payload
	 * @param mixed $default
	 * @return mixed
	 */
	private function persistedOrDefaultArray(array $payload, string $key, mixed $default): mixed
	{
		$value = $payload[$key] ?? null;
		if (is_array($value)) {
			return $value;
		}

		return $default;
	}
}
