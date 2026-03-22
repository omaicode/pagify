<?php

namespace Pagify\PageBuilder\Webstudio\Services;

class RegisteredComponentDefinitionNormalizerService
{
	/**
	 * @param array<string, mixed> $definition
	 * @return array<string, mixed>|null
	 */
	public function normalize(array $definition): ?array
	{
		$key = trim((string) ($definition['key'] ?? ''));
		if ($key === '') {
			return null;
		}

		$owner = trim((string) ($definition['owner'] ?? 'core'));
		if ($owner === '') {
			$owner = 'core';
		}

		if (! str_contains($key, ':')) {
			$key = $owner . ':' . $key;
		}

		$ownerType = strtolower(trim((string) ($definition['owner_type'] ?? 'module')));
		if (! in_array($ownerType, ['module', 'plugin'], true)) {
			$ownerType = 'module';
		}

		$class = $this->normalizeClassValue($definition['class'] ?? null);
		$style = $this->normalizeStyleValue($definition['style'] ?? null);
		$attributes = $this->normalizeAttributes($definition['attributes'] ?? []);
		$text = is_scalar($definition['text'] ?? null) ? trim((string) $definition['text']) : '';
		$innerHtml = is_string($definition['inner_html'] ?? null) ? trim($definition['inner_html']) : '';

		$resolved = [
			...$definition,
			'key' => $key,
			'label' => (string) ($definition['label'] ?? $key),
			'icon' => (string) ($definition['icon'] ?? '🧩'),
			'category' => (string) ($definition['category'] ?? 'Registered Components'),
			'description' => (string) ($definition['description'] ?? ''),
			'owner' => $owner,
			'owner_type' => $ownerType,
			'source' => (string) ($definition['source'] ?? ($ownerType === 'plugin' ? 'plugin' : 'hook')),
			'props_schema' => is_array($definition['props_schema'] ?? null) ? (array) $definition['props_schema'] : [],
			'element' => trim((string) ($definition['element'] ?? '')),
			'tag' => trim((string) ($definition['tag'] ?? '')),
			'class' => $class,
			'style' => $style,
			'attributes' => $attributes,
			'text' => $text,
			'inner_html' => $innerHtml,
			'children' => is_array($definition['children'] ?? null) ? array_values($definition['children']) : [],
			'dynamic_data' => is_array($definition['dynamic_data'] ?? null) ? $definition['dynamic_data'] : [],
		];

		$htmlTemplate = trim((string) ($definition['html_template'] ?? ''));
		$resolved['html_template'] = $htmlTemplate !== '' ? $htmlTemplate : $this->buildHtmlTemplate($resolved);

		return $resolved;
	}

	private function normalizeClassValue(mixed $value): string
	{
		if (is_string($value)) {
			return trim($value);
		}

		if (! is_array($value)) {
			return '';
		}

		$tokens = [];
		foreach ($value as $token) {
			if (! is_scalar($token)) {
				continue;
			}

			$token = trim((string) $token);
			if ($token !== '') {
				$tokens[] = $token;
			}
		}

		return implode(' ', $tokens);
	}

	private function normalizeStyleValue(mixed $value): string
	{
		if (is_string($value)) {
			return trim($value);
		}

		if (! is_array($value)) {
			return '';
		}

		$tokens = [];
		foreach ($value as $property => $propertyValue) {
			if (! is_string($property) || trim($property) === '' || ! is_scalar($propertyValue)) {
				continue;
			}

			$tokens[] = trim($property) . ': ' . trim((string) $propertyValue);
		}

		return implode('; ', $tokens);
	}

	/**
	 * @return array<string, string>
	 */
	private function normalizeAttributes(mixed $value): array
	{
		if (! is_array($value)) {
			return [];
		}

		$attributes = [];
		foreach ($value as $name => $attributeValue) {
			if (! is_string($name) || trim($name) === '') {
				continue;
			}

			$name = trim($name);
			if (is_bool($attributeValue)) {
				if ($attributeValue) {
					$attributes[$name] = $name;
				}

				continue;
			}

			if (is_scalar($attributeValue)) {
				$attributes[$name] = trim((string) $attributeValue);
			}
		}

		return $attributes;
	}

	/**
	 * @param array<string, mixed> $component
	 */
	private function buildHtmlTemplate(array $component): string
	{
		$tag = trim((string) ($component['element'] ?? $component['tag'] ?? ''));
		if ($tag === '') {
			$tag = 'div';
		}

		$attributes = $this->normalizeAttributes($component['attributes'] ?? []);
		$class = trim((string) ($component['class'] ?? ''));
		$style = trim((string) ($component['style'] ?? ''));

		if ($class !== '') {
			$attributes['class'] = $class;
		}

		if ($style !== '') {
			$attributes['style'] = $style;
		}

		$text = trim((string) ($component['text'] ?? ''));
		$innerHtml = trim((string) ($component['inner_html'] ?? ''));

		$content = $innerHtml !== '' ? $innerHtml : ($text !== '' ? e($text) : '');

		$children = is_array($component['children'] ?? null) ? $component['children'] : [];
		foreach ($children as $child) {
			$content .= $this->renderChildNode($child);
		}

		$attributePairs = [];
		foreach ($attributes as $name => $value) {
			$attributePairs[] = sprintf('%s="%s"', $name, e((string) $value));
		}

		$attributeString = $attributePairs === [] ? '' : ' ' . implode(' ', $attributePairs);

		return sprintf('<%s%s>%s</%s>', $tag, $attributeString, $content, $tag);
	}

	private function renderChildNode(mixed $child): string
	{
		if (! is_array($child)) {
			return '';
		}

		$tag = trim((string) ($child['element'] ?? $child['tag'] ?? ''));
		if ($tag === '') {
			$tag = 'div';
		}

		$attributes = $this->normalizeAttributes($child['attributes'] ?? []);
		$class = $this->normalizeClassValue($child['class'] ?? null);
		$style = $this->normalizeStyleValue($child['style'] ?? null);
		if ($class !== '') {
			$attributes['class'] = $class;
		}
		if ($style !== '') {
			$attributes['style'] = $style;
		}

		$innerHtml = is_string($child['inner_html'] ?? null) ? trim((string) $child['inner_html']) : '';
		$text = is_scalar($child['text'] ?? null) ? trim((string) $child['text']) : '';
		$content = $innerHtml !== '' ? $innerHtml : ($text !== '' ? e($text) : '');

		$nested = is_array($child['children'] ?? null) ? $child['children'] : [];
		foreach ($nested as $nestedChild) {
			$content .= $this->renderChildNode($nestedChild);
		}

		$attributePairs = [];
		foreach ($attributes as $name => $value) {
			$attributePairs[] = sprintf('%s="%s"', $name, e((string) $value));
		}
		$attributeString = $attributePairs === [] ? '' : ' ' . implode(' ', $attributePairs);

		return sprintf('<%s%s>%s</%s>', $tag, $attributeString, $content, $tag);
	}
}
