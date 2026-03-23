<?php

namespace Pagify\Core\Wsre\Resolvers\PageBuilder;

use Illuminate\Support\Arr;

class PageBuilderStylesheetRenderer
{
	/**
	 * @var array<string, string>
	 */
	private array $cacheByInterfaceHash = [];

	private const CACHE_LIMIT = 256;

	/**
	 * @param array<int, array<string, mixed>> $styles
	 * @param array<string, array<int, string>> $sourceIdsByInstance
	 * @param array<string, array<string, mixed>> $breakpointById
	 */
	public function render(array $styles, array $sourceIdsByInstance, array $breakpointById, ?string $interfaceHash = null): string
	{
		$normalizedHash = is_string($interfaceHash) ? trim($interfaceHash) : '';
		if ($normalizedHash !== '' && array_key_exists($normalizedHash, $this->cacheByInterfaceHash)) {
			return $this->cacheByInterfaceHash[$normalizedHash];
		}

		$instanceBySource = [];
		foreach ($sourceIdsByInstance as $instanceId => $sourceIds) {
			foreach ($sourceIds as $sourceId) {
				$instanceBySource[$sourceId][] = $instanceId;
			}
		}

		$baseRules = [];
		$mediaRules = [];

		foreach ($styles as $style) {
			if (! is_array($style)) {
				continue;
			}
			$sourceId = trim((string) ($style['styleSourceId'] ?? ''));
			$property = trim((string) ($style['property'] ?? ''));
			if ($sourceId === '' || $property === '') {
				continue;
			}

			$instanceIds = $instanceBySource[$sourceId] ?? [];
			if ($instanceIds === []) {
				continue;
			}

			$value = $this->styleValueToCss($style['value'] ?? null);
			if ($value === '') {
				continue;
			}

			$cssProperty = strtolower((string) preg_replace('/([a-z])([A-Z])/', '$1-$2', $property));
			$breakpointId = trim((string) ($style['breakpointId'] ?? ''));

			foreach ($instanceIds as $instanceId) {
				$selector = '[data-ws-id="' . addslashes($instanceId) . '"]';
				$declaration = $cssProperty . ':' . $value . ';';

				if ($breakpointId !== '' && isset($breakpointById[$breakpointId]['maxWidth'])) {
					$maxWidth = (int) $breakpointById[$breakpointId]['maxWidth'];
					$mediaRules[$maxWidth][$selector][] = $declaration;
				} else {
					$baseRules[$selector][] = $declaration;
				}
			}
		}

		$css = '';
		foreach ($baseRules as $selector => $declarations) {
			$css .= $selector . '{' . implode('', array_unique($declarations)) . '}';
		}

		ksort($mediaRules);
		foreach ($mediaRules as $maxWidth => $ruleSet) {
			$inner = '';
			foreach ($ruleSet as $selector => $declarations) {
				$inner .= $selector . '{' . implode('', array_unique($declarations)) . '}';
			}
			if ($inner !== '') {
				$css .= '@media (max-width:' . $maxWidth . 'px){' . $inner . '}';
			}
		}

		if ($normalizedHash !== '') {
			$this->cacheByInterfaceHash[$normalizedHash] = $css;
			$this->trimCache();
		}

		return $css;
	}

	private function trimCache(): void
	{
		while (count($this->cacheByInterfaceHash) > self::CACHE_LIMIT) {
			$oldestKey = array_key_first($this->cacheByInterfaceHash);
			if ($oldestKey === null) {
				break;
			}

			unset($this->cacheByInterfaceHash[$oldestKey]);
		}
	}

	private function styleValueToCss(mixed $value): string
	{
		if (is_scalar($value)) {
			return trim((string) $value);
		}

		if (! is_array($value)) {
			return '';
		}

		$type = trim((string) ($value['type'] ?? ''));
		if ($type === 'keyword' || $type === 'string') {
			return trim((string) ($value['value'] ?? ''));
		}

		if ($type === 'unit') {
			$numeric = Arr::get($value, 'value');
			$unit = trim((string) Arr::get($value, 'unit', ''));
			if (! is_numeric($numeric)) {
				return '';
			}
			return (string) $numeric . $unit;
		}

		if ($type === 'rgb' || $type === 'hsl') {
			$raw = Arr::get($value, 'value');
			return is_string($raw) ? trim($raw) : '';
		}

		return '';
	}
}
