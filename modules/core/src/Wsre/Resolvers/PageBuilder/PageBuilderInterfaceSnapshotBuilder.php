<?php

namespace Pagify\Core\Wsre\Resolvers\PageBuilder;

class PageBuilderInterfaceSnapshotBuilder
{
	/**
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>|null
	 */
	public function build(array $params): ?array
	{
		$interface = is_array($params['interface'] ?? null) ? (array) $params['interface'] : [];
		if ($interface === []) {
			return null;
		}

		$interfaceHash = $this->computeInterfaceHash($interface);

		$instancesList = is_array($interface['instances'] ?? null) ? (array) $interface['instances'] : [];
		$propsList = is_array($interface['props'] ?? null) ? (array) $interface['props'] : [];
		$stylesList = is_array($interface['styles'] ?? null) ? (array) $interface['styles'] : [];
		$selections = is_array($interface['styleSourceSelections'] ?? null) ? (array) $interface['styleSourceSelections'] : [];
		$breakpoints = is_array($interface['breakpoints'] ?? null) ? (array) $interface['breakpoints'] : [];
		$dataSourcesList = is_array($interface['dataSources'] ?? null) ? (array) $interface['dataSources'] : [];
		$resourcesList = is_array($interface['resources'] ?? null) ? (array) $interface['resources'] : [];
		$variableValuesRaw = is_array($interface['variableValues'] ?? null) ? (array) $interface['variableValues'] : [];

		$instances = [];
		foreach ($instancesList as $instance) {
			if (! is_array($instance)) {
				continue;
			}
			$id = trim((string) ($instance['id'] ?? ''));
			if ($id === '') {
				continue;
			}
			$instances[$id] = $instance;
		}

		if ($instances === []) {
			return null;
		}

		$propsByInstance = [];
		foreach ($propsList as $prop) {
			if (! is_array($prop)) {
				continue;
			}
			$instanceId = trim((string) ($prop['instanceId'] ?? ''));
			$name = trim((string) ($prop['name'] ?? ''));
			if ($instanceId === '' || $name === '') {
				continue;
			}
			$propsByInstance[$instanceId][] = [
				'name' => $name,
				'type' => (string) ($prop['type'] ?? ''),
				'value' => $prop['value'] ?? null,
			];
		}

		$dataSourcesById = [];
		foreach ($dataSourcesList as $item) {
			if (! is_array($item)) {
				continue;
			}
			$id = trim((string) ($item['id'] ?? ''));
			if ($id === '') {
				continue;
			}
			$dataSourcesById[$id] = $item;
		}

		$resourcesById = [];
		foreach ($resourcesList as $item) {
			if (! is_array($item)) {
				continue;
			}
			$id = trim((string) ($item['id'] ?? ''));
			if ($id === '') {
				continue;
			}
			$resourcesById[$id] = $item;
		}

		$sourceIdsByInstance = [];
		foreach ($selections as $selection) {
			if (! is_array($selection)) {
				continue;
			}
			$instanceId = trim((string) ($selection['instanceId'] ?? ''));
			if ($instanceId === '') {
				continue;
			}
			$sourceIdsByInstance[$instanceId] = array_values(array_filter(array_map(
				static fn (mixed $id): string => is_string($id) ? trim($id) : '',
				(array) ($selection['values'] ?? [])
			), static fn (string $id): bool => $id !== ''));
		}

		$breakpointById = [];
		foreach ($breakpoints as $breakpoint) {
			if (! is_array($breakpoint)) {
				continue;
			}
			$id = trim((string) ($breakpoint['id'] ?? ''));
			if ($id === '') {
				continue;
			}
			$breakpointById[$id] = $breakpoint;
		}

		$rootId = trim((string) ($params['root_instance_id'] ?? ''));
		if ($rootId === '' || ! isset($instances[$rootId])) {
			$fallbackPageId = (string) ($params['page_id'] ?? '');
			$candidateId = $fallbackPageId !== '' ? 'root-' . $fallbackPageId : '';
			if ($candidateId !== '' && isset($instances[$candidateId])) {
				$rootId = $candidateId;
			} else {
				$rootId = array_key_first($instances) ?? '';
			}
		}

		if ($rootId === '') {
			return null;
		}

		return [
			'interfaceHash' => $interfaceHash,
			'rootId' => $rootId,
			'instances' => $instances,
			'propsByInstance' => $propsByInstance,
			'stylesList' => $stylesList,
			'sourceIdsByInstance' => $sourceIdsByInstance,
			'breakpointById' => $breakpointById,
			'dataSourcesById' => $dataSourcesById,
			'resourcesById' => $resourcesById,
			'variableValuesRaw' => $variableValuesRaw,
		];
	}

	/**
	 * @param array<string, mixed> $interface
	 */
	private function computeInterfaceHash(array $interface): string
	{
		$encoded = json_encode($interface, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if (is_string($encoded)) {
			return sha1($encoded);
		}

		return sha1(serialize($interface));
	}
}
