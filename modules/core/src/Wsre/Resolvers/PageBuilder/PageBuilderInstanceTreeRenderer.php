<?php

namespace Pagify\Core\Wsre\Resolvers\PageBuilder;

class PageBuilderInstanceTreeRenderer
{
	public function __construct(
		private readonly PageBuilderVariableResolver $variableResolver,
	) {
	}

	/**
	 * @param array<string, array<string, mixed>> $instances
	 * @param array<string, array<int, array<string, mixed>>> $propsByInstance
	 * @param array<string, mixed> $variableValues
	 */
	public function render(string $rootId, array $instances, array $propsByInstance, array $variableValues): string
	{
		return $this->renderInstance($rootId, $instances, $propsByInstance, $variableValues, true);
	}

	/**
	 * @param array<string, array<string, mixed>> $instances
	 * @param array<string, array<int, array<string, mixed>>> $propsByInstance
	 * @param array<string, mixed> $variableValues
	 */
	private function renderInstance(string $instanceId, array $instances, array $propsByInstance, array $variableValues, bool $isRoot = false): string
	{
		$instance = $instances[$instanceId] ?? null;
		if (! is_array($instance)) {
			return '';
		}

		$component = trim((string) ($instance['component'] ?? ''));
		if ($component === 'ws:block-template') {
			return '';
		}

		$childrenHtml = $this->renderChildren((array) ($instance['children'] ?? []), $instances, $propsByInstance, $variableValues);

		if ($component === 'ws:block') {
			return $childrenHtml;
		}

		$tag = trim((string) ($instance['tag'] ?? ''));
		if ($tag === '') {
			$tag = 'div';
		}

		if ($isRoot && in_array(strtolower($tag), ['body', 'html'], true)) {
			return $childrenHtml;
		}

		$attrs = $this->renderAttributes($propsByInstance[$instanceId] ?? [], $variableValues);
		$attrs[] = 'data-ws-id="' . e($instanceId) . '"';
		$attrString = implode(' ', array_values(array_filter($attrs)));
		$attrString = $attrString !== '' ? ' ' . $attrString : '';

		return '<' . $tag . $attrString . '>' . $childrenHtml . '</' . $tag . '>';
	}

	/**
	 * @param array<int, array<string, mixed>> $children
	 * @param array<string, array<string, mixed>> $instances
	 * @param array<string, array<int, array<string, mixed>>> $propsByInstance
	 * @param array<string, mixed> $variableValues
	 */
	private function renderChildren(array $children, array $instances, array $propsByInstance, array $variableValues): string
	{
		$html = '';
		foreach ($children as $child) {
			if (! is_array($child)) {
				continue;
			}
			$type = trim((string) ($child['type'] ?? ''));
			if ($type === 'text') {
				$value = $child['value'] ?? '';
				$html .= e(is_scalar($value) ? (string) $value : '');
				continue;
			}
			if ($type !== 'id') {
				continue;
			}
			$childId = trim((string) ($child['value'] ?? ''));
			if ($childId === '') {
				continue;
			}
			$html .= $this->renderInstance($childId, $instances, $propsByInstance, $variableValues);
		}

		return $html;
	}

	/**
	 * @param array<int, array<string, mixed>> $props
	 * @param array<string, mixed> $variableValues
	 * @return array<int, string>
	 */
	private function renderAttributes(array $props, array $variableValues): array
	{
		$attributes = [];
		foreach ($props as $prop) {
			if (! is_array($prop)) {
				continue;
			}

			$attrName = trim((string) ($prop['name'] ?? ''));
			if ($attrName === '' || str_starts_with($attrName, '$')) {
				continue;
			}

			if (in_array($attrName, ['children', 'innerHTML', 'html'], true)) {
				continue;
			}

			$value = $this->variableResolver->resolvePropValue($prop, $variableValues);
			if ($value === null) {
				continue;
			}

			if (is_bool($value)) {
				if ($value) {
					$attributes[] = $attrName;
				}
				continue;
			}

			if (is_scalar($value)) {
				$attributes[] = $attrName . '="' . e((string) $value) . '"';
			}
		}

		return $attributes;
	}
}
