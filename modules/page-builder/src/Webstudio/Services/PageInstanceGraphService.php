<?php

namespace Pagify\PageBuilder\Webstudio\Services;

use Pagify\PageBuilder\Models\Page;

class PageInstanceGraphService
{
	/**
	 * @param array<int, array<string, mixed>> $instances
	 * @return array<int, array<string, mixed>>
	 */
	public function extractForPage(array $instances, Page $page, ?string $preferredRootId = null): array
	{
		$byId = [];
		foreach ($instances as $instance) {
			if (! is_array($instance)) {
				continue;
			}

			$instanceId = isset($instance['id']) && is_string($instance['id']) ? $instance['id'] : '';
			if ($instanceId === '') {
				continue;
			}

			$byId[$instanceId] = $instance;
		}

		$candidateRootIds = [];
		if (is_string($preferredRootId) && $preferredRootId !== '') {
			$candidateRootIds[] = $preferredRootId;
		}
		$candidateRootIds[] = 'root-'.(string) $page->id;

		$rootId = null;
		foreach ($candidateRootIds as $candidateRootId) {
			if (isset($byId[$candidateRootId])) {
				$rootId = $candidateRootId;
				break;
			}
		}

		if ($rootId === null) {
			return [];
		}

		$selected = [];
		$visited = [];
		$stack = [$rootId];

		while ($stack !== []) {
			$currentId = array_pop($stack);
			if (! is_string($currentId) || $currentId === '' || isset($visited[$currentId])) {
				continue;
			}

			$visited[$currentId] = true;
			$current = $byId[$currentId] ?? null;
			if (! is_array($current)) {
				continue;
			}

			$selected[] = $current;

			$children = $current['children'] ?? null;
			if (! is_array($children)) {
				continue;
			}

			foreach ($children as $child) {
				if (is_array($child)) {
					$childType = isset($child['type']) && is_string($child['type']) ? $child['type'] : null;
					$childValue = isset($child['value']) && is_string($child['value']) ? $child['value'] : null;
					if ($childType === 'id' && $childValue !== null && $childValue !== '') {
						$stack[] = $childValue;
					}
					continue;
				}

				if (is_string($child) && $child !== '') {
					$stack[] = $child;
				}
			}
		}

		return $selected;
	}

	/**
	 * Guarantee selected page always has a root instance in compatibility payload.
	 * This prevents Webstudio navigator/canvas from entering invalid state when
	 * persisted snapshots are stale or missing the current page root node.
	 *
	 * @param array<int, array<string, mixed>> $instances
	 * @return array<int, array<string, mixed>>
	 */
	public function ensurePageRoot(array $instances, Page $page): array
	{
		$expectedRootId = 'root-' . (string) $page->id;

		foreach ($instances as $instance) {
			$instanceId = is_array($instance) && isset($instance['id']) && is_string($instance['id'])
				? $instance['id']
				: '';

			if ($instanceId === $expectedRootId) {
				return $instances;
			}
		}

		array_unshift($instances, [
			'id' => $expectedRootId,
			'type' => 'instance',
			'component' => 'ws:element',
			'children' => [],
			'tag' => 'body',
		]);

		return $instances;
	}
}
