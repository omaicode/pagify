<?php

namespace Pagify\PageBuilder\Webstudio\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Pagify\Core\Http\Controllers\Api\ApiController;
use Pagify\Core\Support\SiteContext;
use Pagify\Media\Models\MediaAsset;
use Pagify\Media\Services\MediaAssetManager;
use Pagify\PageBuilder\Webstudio\Http\Controllers\Api\Concerns\InteractsWithProjectState;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Models\PageBuilderState;
use Pagify\PageBuilder\Services\EditorAccessTokenService;
use Pagify\PageBuilder\Services\PageFolderService;
use Pagify\PageBuilder\Services\PageService;
use Pagify\PageBuilder\Webstudio\Services\BuildStatePayloadService;
use Pagify\PageBuilder\Webstudio\Services\ComponentDefinitionDiscoveryService;
use Pagify\PageBuilder\Webstudio\Services\ComponentDynamicRendererService;
use Pagify\PageBuilder\Webstudio\Services\PageInstanceGraphService;
use Pagify\PageBuilder\Webstudio\Services\PatchStatePersistenceService;
use Pagify\PageBuilder\Webstudio\Services\ProjectAssetPayloadService;
use Pagify\PageBuilder\Webstudio\Services\RegisteredComponentPayloadMapperService;
use Pagify\PageBuilder\Webstudio\Services\RegisteredComponentResolverService;

class CompatController extends ApiController
{
	use InteractsWithProjectState;

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

	public function data(Request $request, string $projectId, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext, PageFolderService $pageFolderService, PageService $pageService, ComponentDefinitionDiscoveryService $componentDiscovery, ComponentDynamicRendererService $dynamicRenderer, RegisteredComponentResolverService $componentResolver, RegisteredComponentPayloadMapperService $componentPayloadMapper, PageInstanceGraphService $instanceGraph, ProjectAssetPayloadService $assetPayload, BuildStatePayloadService $buildStatePayload): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'page:read');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		$siteId = isset($claims['site_id']) ? (int) $claims['site_id'] : null;
		$pageService->ensureHomePageExists(isset($claims['admin_id']) ? (int) $claims['admin_id'] : null);

		$allPages = Page::query()->orderBy('folder_order')->orderBy('id')->get();
		$rootPage = $allPages->first();
		$page = $rootPage instanceof Page ? $rootPage : $this->resolvePage($claims, $projectId);
		$builderState = $page !== null
			? PageBuilderState::query()->where('page_id', $page->id)->first()
			: null;
		$version = $builderState?->version ?? $this->currentVersion($projectId, $page);
		$state = $this->readProjectRuntimeState($projectId, $page, $siteContext);
		$pageBundle = $pageFolderService->buildPagesBundle($projectId, $page, $allPages, $siteId);
		$persistedBuildData = $buildStatePayload->dataFromState($builderState);
		$rootInstances = $buildStatePayload->value($persistedBuildData, 'instances', $pageBundle['instances']);
		if ($rootPage instanceof Page) {
			$rootInstances = $buildStatePayload->resolveRootInstances($projectId, $rootInstances, $rootPage, $instanceGraph);
		}
		$nowIso = now()->toIso8601String();

		$resolvedBlocks = $dynamicRenderer->render(
			$componentResolver->resolve($componentDiscovery->discover()),
			$page,
			$state,
		);

		$response = [
			'id' => (string) ($builderState?->build_id ?: ('build-'.$projectId)),
			'version' => $version,
			'projectId' => (string) $projectId,
			'createdAt' => $nowIso,
			'updatedAt' => $nowIso,
			'project' => $page !== null
			? $this->projectPayload($projectId, $page, $state)
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
			'assets' => $assetPayload->listForProject($projectId),
			'instances' => $rootInstances,
			'props' => $buildStatePayload->value($persistedBuildData, 'props', []),
			'dataSources' => $buildStatePayload->value($persistedBuildData, 'dataSources', []),
			'resources' => $buildStatePayload->value($persistedBuildData, 'resources', []),
			'breakpoints' => $buildStatePayload->value($persistedBuildData, 'breakpoints', [
				['id' => 'aqVX0VTx9bxHqRd0MS3a3', 'label' => 'Base'],
				['id' => 'wS1by9VwuSNP-NjL5yM7k', 'label' => 'Tablet', 'maxWidth' => 991],
				['id' => 'KlGACE3pdP2LWNWyLD8dL', 'label' => 'Mobile landscape', 'maxWidth' => 767],
				['id' => 'SH8TDdiQpG6IF3gDM0ZJU', 'label' => 'Mobile portrait', 'maxWidth' => 479],
			]),
			'styleSources' => $buildStatePayload->value($persistedBuildData, 'styleSources', []),
			'styleSourceSelections' => $buildStatePayload->value($persistedBuildData, 'styleSourceSelections', []),
			'styles' => $buildStatePayload->value($persistedBuildData, 'styles', []),
			'marketplaceProduct' => (object) [],
			// Page tree metadata should stay authoritative from Pagify DB/API,
			// not from persisted builder snapshots, so publish status/title/slug
			// always reflect the latest server state when switching pages.
			'pages' => $pageBundle['pages'],
			'publisherHost' => (string) ($state['publisherHost'] ?? parse_url($request->getSchemeAndHttpHost(), PHP_URL_HOST) ?: 'localhost'),
			'registeredComponents' => $componentPayloadMapper->map($resolvedBlocks),
		];

		return response()->json($response);
	}

	public function dataPage(Request $request, string $projectId, string $pageId, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext, PageInstanceGraphService $instanceGraph, BuildStatePayloadService $buildStatePayload): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'page:read');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		$page = $this->resolvePage($claims, $pageId);
		if (! $page instanceof Page) {
			return response()->json([
				'status' => 'error',
				'errors' => 'Page not found',
			], 404);
		}

		$builderState = PageBuilderState::query()->withoutGlobalScopes()->where('page_id', $page->id)->first();
		$version = $builderState?->version ?? $this->currentVersion($projectId, $page);
		$persistedBuildData = $buildStatePayload->dataFromState($builderState);

		$defaultInstances = [[
			'id' => 'root-'.(string) $page->id,
			'type' => 'instance',
			'component' => 'ws:element',
			'children' => [],
			'tag' => 'body',
		]];

		$instances = $buildStatePayload->value($persistedBuildData, 'instances', $defaultInstances);
		$instances = $buildStatePayload->resolvePageInstances($projectId, $page, $instances, $instanceGraph);

		return response()->json([
			'id' => (string) ($builderState?->build_id ?: ('build-'.$projectId)),
			'version' => $version,
			'projectId' => (string) $projectId,
			'pageId' => (string) $page->id,
			'instances' => $instances,
			'props' => $buildStatePayload->value($persistedBuildData, 'props', []),
			'dataSources' => $buildStatePayload->value($persistedBuildData, 'dataSources', []),
			'resources' => $buildStatePayload->value($persistedBuildData, 'resources', []),
			'breakpoints' => $buildStatePayload->value($persistedBuildData, 'breakpoints', [
				['id' => 'aqVX0VTx9bxHqRd0MS3a3', 'label' => 'Base'],
				['id' => 'wS1by9VwuSNP-NjL5yM7k', 'label' => 'Tablet', 'maxWidth' => 991],
				['id' => 'KlGACE3pdP2LWNWyLD8dL', 'label' => 'Mobile landscape', 'maxWidth' => 767],
				['id' => 'SH8TDdiQpG6IF3gDM0ZJU', 'label' => 'Mobile portrait', 'maxWidth' => 479],
			]),
			'styleSources' => $buildStatePayload->value($persistedBuildData, 'styleSources', []),
			'styleSourceSelections' => $buildStatePayload->value($persistedBuildData, 'styleSourceSelections', []),
			'styles' => $buildStatePayload->value($persistedBuildData, 'styles', []),
		]);
	}

	public function patch(Request $request, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext, PageInstanceGraphService $instanceGraph, PatchStatePersistenceService $patchPersistence): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'page:write');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		$payload = $request->json()->all();
		$projectId = (string) ($payload['projectId'] ?? '');
		$pageId = isset($payload['pageId']) && is_numeric($payload['pageId'])
			? (int) $payload['pageId']
			: null;
		$pageRootInstanceId = isset($payload['pageRootInstanceId']) && is_string($payload['pageRootInstanceId'])
			? trim($payload['pageRootInstanceId'])
			: null;
		$clientVersion = (int) ($payload['version'] ?? 0);

		if ($projectId === '') {
			return response()->json([
				'status' => 'error',
				'errors' => 'Project id required',
			], 400);
		}

		$page = $pageId !== null && $pageId > 0
			? $this->resolvePage($claims, (string) $pageId)
			: $this->resolvePage($claims, $projectId);
		if (! $page instanceof Page) {
			return response()->json([
				'status' => 'error',
				'errors' => 'Unable to resolve target page for this patch request.',
			], 422);
		}
		$serverVersion = $this->currentVersion($projectId, $page);

		if ($clientVersion !== $serverVersion) {
			return response()->json([
				'status' => 'version_mismatched',
				'errors' => 'Builder data version changed. Please reload editor to continue.',
			]);
		}

		$nextVersion = $serverVersion + 1;
		Cache::put($this->versionCacheKey($projectId), $nextVersion, now()->addDay());

		$instancesPayload = [];
		if (isset($payload['state']) && is_array($payload['state'])) {
			$statePayload = (array) $payload['state'];
			$instancesPayload = $instanceGraph->extractForPage(
				is_array($statePayload['instances'] ?? null) ? (array) $statePayload['instances'] : [],
				$page,
				$pageRootInstanceId
			);
		}

		$patchPersistence->persist($projectId, $page, $nextVersion, $payload, $instancesPayload);

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

	public function assets(Request $request, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext, ProjectAssetPayloadService $assetPayload): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'media:read');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		if ($request->isMethod('get')) {
			$projectId = (string) ($request->query('projectId') ?? $request->query('pageId') ?? (string) ($claims['page_id'] ?? ''));

			return response()->json($assetPayload->listForProject($projectId));
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

	public function uploadAsset(Request $request, string $name, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext, MediaAssetManager $assetManager, ProjectAssetPayloadService $assetPayload): JsonResponse
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
		);

		$asset = $assetManager->upload(
			file: $uploadedFile,
			siteId: isset($claims['site_id']) ? (int) $claims['site_id'] : null,
			uploadedByAdminId: isset($claims['admin_id']) ? (int) $claims['admin_id'] : null,
		);

		@unlink($tmpPath);

		return response()->json([
			'uploadedAssets' => [$assetPayload->mapOne($asset, (string) ($claims['page_id'] ?? ''))],
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
