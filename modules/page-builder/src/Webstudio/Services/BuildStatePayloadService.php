<?php

namespace Pagify\PageBuilder\Webstudio\Services;

use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Models\PageBuilderInstance;
use Pagify\PageBuilder\Models\PageBuilderState;

class BuildStatePayloadService
{
	/**
	 * @return array<string, mixed>
	 */
	public function dataFromState(?PageBuilderState $builderState): array
	{
		return is_array($builderState?->data_json)
			? (array) $builderState->data_json
			: [];
	}

	/**
	 * @param array<string, mixed> $payload
	 * @param mixed $default
	 * @return mixed
	 */
	public function value(array $payload, string $key, mixed $default): mixed
	{
		$value = $payload[$key] ?? null;
		if (is_array($value)) {
			return $value;
		}

		return $default;
	}

	/**
	 * @param array<int, array<string, mixed>> $fallbackInstances
	 * @return array<int, array<string, mixed>>
	 */
	public function resolveRootInstances(string $projectId, array $fallbackInstances, Page $rootPage, PageInstanceGraphService $instanceGraph): array
	{
		$resolved = $fallbackInstances;
		$persistedRootInstances = PageBuilderInstance::query()
			->withoutGlobalScopes()
			->where('project_id', $projectId)
			->where('page_id', $rootPage->id)
			->value('instances_json');

		if (is_array($persistedRootInstances)) {
			$resolved = $persistedRootInstances;
		}

		return $instanceGraph->extractForPage($resolved, $rootPage);
	}

	/**
	 * @param array<int, array<string, mixed>> $fallbackInstances
	 * @return array<int, array<string, mixed>>
	 */
	public function resolvePageInstances(string $projectId, Page $page, array $fallbackInstances, PageInstanceGraphService $instanceGraph): array
	{
		$resolved = $fallbackInstances;
		$persistedPageInstances = PageBuilderInstance::query()
			->withoutGlobalScopes()
			->where('project_id', $projectId)
			->where('page_id', $page->id)
			->value('instances_json');

		if (is_array($persistedPageInstances)) {
			$resolved = $persistedPageInstances;
		}

		$resolved = $instanceGraph->extractForPage($resolved, $page);

		return $instanceGraph->ensurePageRoot($resolved, $page);
	}
}
