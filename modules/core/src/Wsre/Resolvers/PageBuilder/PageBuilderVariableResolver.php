<?php

namespace Pagify\Core\Wsre\Resolvers\PageBuilder;

class PageBuilderVariableResolver
{
	/**
	 * @param array<string, mixed> $variableValuesRaw
	 * @param array<string, array<string, mixed>> $dataSourcesById
	 * @param array<string, array<string, mixed>> $resourcesById
	 * @return array<string, mixed>
	 */
	public function compileValues(array $variableValuesRaw, array $dataSourcesById, array $resourcesById): array
	{
		$variableValues = [];
		foreach ($variableValuesRaw as $id => $value) {
			if (! is_string($id) || trim($id) === '') {
				continue;
			}
			$variableValues[$id] = $value;
		}

		foreach ($dataSourcesById as $dataSourceId => $dataSource) {
			if (array_key_exists($dataSourceId, $variableValues)) {
				continue;
			}

			$type = trim((string) ($dataSource['type'] ?? ''));
			if ($type === 'variable' && is_array($dataSource['value'] ?? null)) {
				$variableValues[$dataSourceId] = $dataSource['value']['value'] ?? null;
				continue;
			}

			if ($type === 'resource') {
				$resourceId = trim((string) ($dataSource['resourceId'] ?? ''));
				$resource = $resourceId !== '' ? ($resourcesById[$resourceId] ?? null) : null;
				if (is_array($resource)) {
					$variableValues[$dataSourceId] = [
						'name' => (string) ($resource['name'] ?? ''),
						'url' => (string) ($resource['url'] ?? ''),
						'method' => (string) ($resource['method'] ?? ''),
					];
				}
			}
		}

		return $variableValues;
	}

	/**
	 * @param array<string, mixed> $prop
	 * @param array<string, mixed> $variableValues
	 */
	public function resolvePropValue(array $prop, array $variableValues): mixed
	{
		$type = trim((string) ($prop['type'] ?? ''));
		$value = $prop['value'] ?? null;

		if ($type === 'parameter' || $type === 'resource') {
			$sourceId = is_scalar($value) ? trim((string) $value) : '';
			return $sourceId !== '' ? ($variableValues[$sourceId] ?? null) : null;
		}

		if ($type === 'expression' && is_string($value)) {
			$dataSourceId = $this->decodeDataSourceId($value);
			if ($dataSourceId !== null && array_key_exists($dataSourceId, $variableValues)) {
				return $variableValues[$dataSourceId];
			}
		}

		return $value;
	}

	private function decodeDataSourceId(string $expression): ?string
	{
		$trimmed = trim($expression);
		if ($trimmed === '$ws$system') {
			return 'system';
		}

		$prefix = '$ws$dataSource$';
		if (! str_starts_with($trimmed, $prefix)) {
			return null;
		}

		$encoded = substr($trimmed, strlen($prefix));
		if (! is_string($encoded) || $encoded === '') {
			return null;
		}

		return str_replace('__DASH__', '-', $encoded);
	}
}
