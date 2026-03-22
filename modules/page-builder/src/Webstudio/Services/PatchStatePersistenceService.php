<?php

namespace Pagify\PageBuilder\Webstudio\Services;

use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Models\PageBuilderInstance;
use Pagify\PageBuilder\Models\PageBuilderState;

class PatchStatePersistenceService
{
	/**
	 * @param array<string, mixed> $payload
	 * @param array<int, array<string, mixed>> $instancesPayload
	 */
	public function persist(string $projectId, Page $page, int $nextVersion, array $payload, array $instancesPayload): void
	{
		$builderState = PageBuilderState::query()->withoutGlobalScopes()->firstOrNew([
			'page_id' => $page->id,
		]);
		$builderState->site_id = $page->site_id;
		$builderState->build_id = (string) ($payload['buildId'] ?? ($builderState->build_id ?: ('build-'.$projectId)));
		$builderState->version = $nextVersion;

		if (isset($payload['state']) && is_array($payload['state'])) {
			$builderState->data_json = (array) $payload['state'];
		}

		$builderState->save();

		$pageBuilderInstance = PageBuilderInstance::query()->withoutGlobalScopes()->firstOrNew([
			'project_id' => $projectId,
			'page_id' => $page->id,
		]);
		$pageBuilderInstance->site_id = $page->site_id;
		$pageBuilderInstance->build_id = (string) ($payload['buildId'] ?? ($pageBuilderInstance->build_id ?: ('build-'.$projectId)));
		$pageBuilderInstance->build_version = $nextVersion;
		$pageBuilderInstance->instances_json = $instancesPayload;
		$pageBuilderInstance->save();
	}
}
