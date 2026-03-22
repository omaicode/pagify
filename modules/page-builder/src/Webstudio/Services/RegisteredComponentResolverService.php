<?php

namespace Pagify\PageBuilder\Webstudio\Services;

use Pagify\Core\Services\EventBus;

class RegisteredComponentResolverService
{
	public function __construct(
		private readonly EventBus $eventBus,
		private readonly RegisteredComponentDefinitionNormalizerService $normalizer,
	) {
	}

	/**
	 * @param array<int, array<string, mixed>> $discoveredDefinitions
	 * @return array<int, array<string, mixed>>
	 */
	public function resolve(array $discoveredDefinitions): array
	{
		$hookDefinitions = $this->normalizeHookDefinitions($this->eventBus->emitHook('page-builder.webstudio.components'));
		$merged = [];

		foreach ([...$discoveredDefinitions, ...$hookDefinitions] as $definition) {
			$normalized = $this->normalizer->normalize($definition);
			if ($normalized === null) {
				continue;
			}

			$merged[$normalized['key']] = $normalized;
		}

		return array_values($merged);
	}

	/**
	 * @param array<int, mixed> $hookResults
	 * @return array<int, array<string, mixed>>
	 */
	private function normalizeHookDefinitions(array $hookResults): array
	{
		$resolved = [];

		foreach ($hookResults as $hookResult) {
			if (! is_array($hookResult)) {
				continue;
			}

			foreach ($hookResult as $item) {
				if (is_array($item)) {
					$resolved[] = $item;
				}
			}
		}

		return $resolved;
	}
}
