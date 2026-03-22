<?php

namespace Pagify\PageBuilder\Webstudio\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Pagify\Core\Support\SiteContext;
use Pagify\PageBuilder\Services\EditorAccessTokenService;
use Pagify\PageBuilder\Services\PageService;
use Pagify\PageBuilder\Services\TrpcEnvelope;
use Pagify\PageBuilder\Webstudio\Http\Controllers\Api\Concerns\InteractsWithProjectState;

class TrpcService
{
	use InteractsWithProjectState;

	public function __construct(
		private readonly PageService $pageService,
	) {}

	public function handle(Request $request, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'page:read');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		$pathRaw = trim((string) $request->route('path', ''));
		if ($pathRaw === '') {
			return response()->json(TrpcEnvelope::error('TRPC path is required.', 400, 'BAD_REQUEST'), 400);
		}

		$isBatch = (string) $request->query('batch', '0') === '1';
		$procedurePaths = $isBatch
		? array_values(array_filter(explode(',', $pathRaw), static fn (string $item): bool => $item !== ''))
		: [$pathRaw];
		$inputs = $this->parseTrpcInputs($request, $isBatch);

		$results = [];
		$status = 200;

		foreach ($procedurePaths as $index => $procedurePath) {
			$input = $inputs[$index] ?? null;
			$mapped = $this->dispatchTrpcProcedure($procedurePath, $input, $claims, $siteContext);

			if (array_key_exists('error', $mapped)) {
				$status = max($status, (int) data_get($mapped, 'error.json.data.httpStatus', 400));
			}

			$results[] = $mapped;
		}

		if ($isBatch) {
			return response()->json($results, $status);
		}

		return response()->json(
			$results[0] ?? TrpcEnvelope::error('TRPC call failed.', 500, 'INTERNAL_SERVER_ERROR'),
			$status
		);
	}

	/**
	 * @return list<mixed>
	 */
	private function parseTrpcInputs(Request $request, bool $isBatch): array
	{
		$raw = $request->isMethod('get')
		? (string) $request->query('input', '')
		: (string) $request->getContent();

		if ($raw === '') {
			return $isBatch ? [] : [null];
		}

		$decoded = json_decode($raw, true);

		if ($isBatch) {
			if (is_array($decoded)) {
				$items = [];
				ksort($decoded);
				foreach ($decoded as $value) {
					$items[] = $value;
				}

				return $items;
			}

			return [];
		}

		return [$decoded];
	}

	/**
	 * @param  array<string, mixed>  $claims
	 * @return array<string, mixed>
	 */
	private function dispatchTrpcProcedure(string $procedurePath, mixed $input, array $claims, SiteContext $siteContext): array
	{
		$procedureInput = $this->normalizeProcedureInput($input);
		$projectId = (string) (data_get($procedureInput, 'projectId') ?? data_get($claims, 'theme') ?? 'unified');
		$page = $this->resolvePage($claims, $projectId);

		switch ($procedurePath) {
			case 'domain.project':
				if ($projectId === '') {
					return TrpcEnvelope::success(['success' => false, 'error' => 'Project not found']);
				}

				$state = $this->readProjectRuntimeState($projectId, $page, $siteContext);

				return TrpcEnvelope::success([
					'success' => true,
					'project' => $page !== null
						? $this->projectPayload($projectId, $page, $state)
						: $this->virtualProjectPayload($projectId, $claims, $state),
				]);

			case 'domain.countTotalDomains':
				$state = $this->readProjectRuntimeState($projectId, $page, $siteContext);
				$count = count((array) ($state['domainsVirtual'] ?? []));

				return TrpcEnvelope::success(['success' => true, 'data' => $count]);

			case 'domain.findDomainRegistrar':
				return TrpcEnvelope::success(['known' => false, 'cnameFlattening' => true]);

			case 'domain.getEntriToken':
				return TrpcEnvelope::success(['success' => false, 'error' => 'Entri integration is not configured in Pagify.']);

			case 'domain.create':
				if ($projectId === '' || $page === null) {
					return TrpcEnvelope::success(['success' => false, 'error' => 'Project not found']);
				}

				$domain = trim((string) data_get($procedureInput, 'domain', ''));
				if ($domain === '') {
					return TrpcEnvelope::success(['success' => false, 'error' => 'Domain is required']);
				}

				$state = $this->readProjectRuntimeState($projectId, $page, $siteContext);
				$domains = array_values((array) ($state['domainsVirtual'] ?? []));

				$domains[] = [
					'projectId' => $projectId,
					'domainId' => (string) Str::uuid(),
					'domain' => $domain,
					'cname' => str_replace('.', '-', $domain),
					'expectedTxtRecord' => 'pagify-verify='.substr(hash('sha256', $projectId.$domain), 0, 12),
					'verified' => true,
					'status' => 'ACTIVE',
					'error' => null,
					'updatedAt' => now()->toIso8601String(),
					'latestBuildVirtual' => null,
				];

				$state['domainsVirtual'] = $domains;
				$this->writeProjectRuntimeState($projectId, $state);

				return TrpcEnvelope::success(['success' => true]);

			case 'domain.updateProjectDomain':
				if ($projectId === '' || $page === null) {
					return TrpcEnvelope::success(['success' => false, 'error' => 'Project not found']);
				}

				$domain = trim((string) data_get($procedureInput, 'domain', ''));
				if ($domain === '') {
					return TrpcEnvelope::success(['success' => false, 'error' => 'Domain is required']);
				}

				$state = $this->readProjectRuntimeState($projectId, $page, $siteContext);
				$state['domain'] = $domain;
				$this->writeProjectRuntimeState($projectId, $state);

				return TrpcEnvelope::success(['success' => true]);

			case 'domain.verify':
			case 'domain.updateStatus':
				if ($projectId === '' || $page === null) {
					return TrpcEnvelope::success(['success' => false, 'error' => 'Project not found']);
				}

				$domainId = (string) data_get($procedureInput, 'domainId', '');
				$domain = trim((string) data_get($procedureInput, 'domain', ''));
				$state = $this->readProjectRuntimeState($projectId, $page, $siteContext);
				$domains = array_values((array) ($state['domainsVirtual'] ?? []));

				$domains = array_map(static function (array $item) use ($domainId, $domain): array {
					$matched = ($domainId !== '' && (string) ($item['domainId'] ?? '') === $domainId)
					|| ($domain !== '' && (string) ($item['domain'] ?? '') === $domain);

					if (! $matched) {
						return $item;
					}

					$item['verified'] = true;
					$item['status'] = 'ACTIVE';
					$item['error'] = null;
					$item['updatedAt'] = now()->toIso8601String();

					return $item;
				}, $domains);

				$state['domainsVirtual'] = $domains;
				$this->writeProjectRuntimeState($projectId, $state);

				return TrpcEnvelope::success(['success' => true]);

			case 'domain.remove':
				if ($projectId === '' || $page === null) {
					return TrpcEnvelope::success(['success' => false, 'error' => 'Project not found']);
				}

				$domainId = (string) data_get($procedureInput, 'domainId', '');
				$state = $this->readProjectRuntimeState($projectId, $page, $siteContext);
				$domains = array_values(array_filter(
					(array) ($state['domainsVirtual'] ?? []),
					static fn (array $item): bool => (string) ($item['domainId'] ?? '') !== $domainId
				));

				$state['domainsVirtual'] = $domains;
				$this->writeProjectRuntimeState($projectId, $state);

				return TrpcEnvelope::success(['success' => true]);

			case 'domain.unpublish':
				if ($projectId === '' || $page === null) {
					return TrpcEnvelope::success(['success' => false, 'message' => 'Project not found']);
				}

				$state = $this->readProjectRuntimeState($projectId, $page, $siteContext);
				$state['latestBuildVirtual'] = null;
				$this->writeProjectRuntimeState($projectId, $state);

				return TrpcEnvelope::success(['success' => true, 'message' => 'Project was unpublished.']);

			case 'domain.publish':
				if ($projectId === '' || $page === null) {
					return TrpcEnvelope::success(['success' => false, 'error' => 'Project not found']);
				}

				$destination = (string) data_get($procedureInput, 'destination', 'saas');
				$state = $this->readProjectRuntimeState($projectId, $page, $siteContext);

				if ($destination === 'static') {
					$name = 'page-builder-'.$projectId.'-'.now()->format('YmdHis').'.zip';
					$state['latestStaticBuild'] = [
						'updatedAt' => now()->toIso8601String(),
						'publishStatus' => 'PUBLISHED',
						'name' => $name,
					];
					$this->incrementPublishCount($state);
					$this->writeProjectRuntimeState($projectId, $state);

					return TrpcEnvelope::success(['success' => true, 'name' => $name]);
				}

				if (! $this->hasScope($claims, 'page:publish')) {
					return TrpcEnvelope::success(['success' => false, 'error' => 'Missing page:publish scope']);
				}

				$this->pageService->publish($page, isset($claims['admin_id']) ? (int) $claims['admin_id'] : null);

				$domains = array_values((array) data_get($procedureInput, 'domains', []));
				$activeDomain = (string) ($domains[0] ?? ($state['domain'] ?? $page->slug));
				$buildVirtual = [
					'buildId' => (string) Str::uuid(),
					'domain' => $activeDomain,
					'publishStatus' => 'PUBLISHED',
					'createdAt' => now()->toIso8601String(),
				];
				$state['latestBuildVirtual'] = $buildVirtual;
				$state['publishedBuilds'][] = [
					'buildId' => $buildVirtual['buildId'],
					'createdAt' => $buildVirtual['createdAt'],
					'domains' => $activeDomain,
				];
				$this->incrementPublishCount($state);
				$this->writeProjectRuntimeState($projectId, $state);

				return TrpcEnvelope::success(['success' => true]);

			case 'project.userPublishCount':
				$state = $this->readProjectRuntimeState($projectId, $page, $siteContext);

				return TrpcEnvelope::success(['success' => true, 'data' => (int) ($state['userPublishCount'] ?? 0)]);

			case 'project.publishedBuilds':
				$state = $this->readProjectRuntimeState($projectId, $page, $siteContext);

				return TrpcEnvelope::success(['success' => true, 'data' => array_values((array) ($state['publishedBuilds'] ?? []))]);

			case 'project.restoreDevelopmentBuild':
				return TrpcEnvelope::success(['success' => true]);

			case 'project.clone':
				if ($page === null) {
					return TrpcEnvelope::error('Project not found', 404, 'NOT_FOUND');
				}

				$newTitle = trim((string) data_get($procedureInput, 'title', $page->title.' (Copy)'));
				$newSlug = Str::slug($newTitle);
				if ($newSlug === '') {
					$newSlug = 'page-copy-'.Str::lower(Str::random(6));
				}

				$created = $this->pageService->create([
					'title' => $newTitle,
					'slug' => $newSlug,
					'status' => 'draft',
					'layout' => (array) ($page->layout_json ?? []),
					'seo_meta' => (array) ($page->seo_meta_json ?? []),
				], isset($claims['admin_id']) ? (int) $claims['admin_id'] : null);

				return TrpcEnvelope::success(['id' => (string) $created->id]);

			case 'project.setMarketplaceApprovalStatus':
				$status = (string) data_get($procedureInput, 'marketplaceApprovalStatus', 'UNLISTED');
				if ($projectId !== '') {
					$state = $this->readProjectRuntimeState($projectId, $page, $siteContext);
					$state['marketplaceApprovalStatus'] = $status;
					$this->writeProjectRuntimeState($projectId, $state);
				}

				return TrpcEnvelope::success(['marketplaceApprovalStatus' => $status]);

			case 'project.updateTags':
				return TrpcEnvelope::success(['success' => true]);

			default:
				return TrpcEnvelope::error(
					'Procedure '.$procedurePath.' is not implemented in Pagify compatibility layer.',
					501,
					'NOT_IMPLEMENTED'
				);
		}
	}

	/**
	 * tRPC can wrap payloads as {"json": {...}, "meta": {...}} in both single and batch modes.
	 * We normalize that shape to the inner json object so compatibility handlers can read stable keys.
	 */
	private function normalizeProcedureInput(mixed $input): mixed
	{
		if (! is_array($input)) {
			return $input;
		}

		if (array_key_exists('json', $input)) {
			return $input['json'];
		}

		return $input;
	}

	/**
	 * @param array<string, mixed> $claims
	 * @param array<string, mixed> $state
	 * @return array<string, mixed>
	 */
	private function virtualProjectPayload(string $projectId, array $claims, array $state): array
	{
		return [
			'id' => $projectId,
			'title' => 'Pagify Project',
			'domain' => (string) ($state['domain'] ?? ('project-'.$projectId)),
			'isDeleted' => false,
			'createdAt' => now()->toIso8601String(),
			'previewImageAssetId' => null,
			'domainsVirtual' => array_values((array) ($state['domainsVirtual'] ?? [])),
			'latestBuildVirtual' => $state['latestBuildVirtual'] ?? null,
			'latestStaticBuild' => $state['latestStaticBuild'] ?? null,
			'marketplaceApprovalStatus' => (string) ($state['marketplaceApprovalStatus'] ?? 'UNLISTED'),
			'tags' => null,
			'previewImageAsset' => null,
			'userId' => isset($claims['admin_id']) ? (string) $claims['admin_id'] : null,
		];
	}
}
