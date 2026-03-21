<?php

namespace Pagify\PageBuilder\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebstudioComponentDefinitionValidator
{
	/**
	 * @param array<string, mixed> $definition
	 * @return array<string, mixed>|null
	 */
	public function validateAndNormalize(array $definition, string $context): ?array
	{
		$key = trim((string) ($definition['key'] ?? $definition['type'] ?? ''));
		if ($key === '') {
			Log::warning('Webstudio custom component is missing key and has been skipped.', ['context' => $context]);

			return null;
		}

		if (preg_match('/^[A-Za-z0-9:_-]+$/', $key) !== 1) {
			Log::warning('Webstudio custom component has invalid key format and has been skipped.', [
				'context' => $context,
				'key' => $key,
			]);

			return null;
		}

		$normalized = $definition;
		$normalized['key'] = $key;
		$normalized['label'] = $this->normalizeLabel($definition, $key);
		$normalized['description'] = trim((string) ($definition['description'] ?? ''));
		$normalized['icon'] = trim((string) ($definition['icon'] ?? '🧩'));
		$normalized['category'] = trim((string) ($definition['category'] ?? 'Registered Components'));
		$normalized['owner'] = trim((string) ($definition['owner'] ?? ''));
		$normalized['owner_type'] = $this->normalizeOwnerType((string) ($definition['owner_type'] ?? 'module'));
		$normalized['props_schema'] = is_array($definition['props_schema'] ?? null)
			? (array) $definition['props_schema']
			: [];
		$normalized['children'] = $this->normalizeChildren($definition['children'] ?? []);

		$normalized['attributes'] = $this->normalizeAttributes($definition['attributes'] ?? []);

		if (isset($definition['class'])) {
			$normalized['class'] = $this->normalizeClassValue($definition['class']);
		}

		if (isset($definition['style'])) {
			$normalized['style'] = $this->normalizeStyleValue($definition['style']);
		}

		return $normalized;
	}

	/**
	 * @param array<string, mixed> $definition
	 */
	private function normalizeLabel(array $definition, string $key): string
	{
		$label = trim((string) ($definition['label'] ?? ''));
		if ($label !== '') {
			return $label;
		}

		$lastSegment = Str::of($key)->afterLast(':')->replace(['-', '_'], ' ')->toString();

		return Str::headline($lastSegment !== '' ? $lastSegment : $key);
	}

	private function normalizeOwnerType(string $ownerType): string
	{
		$ownerType = strtolower(trim($ownerType));

		return in_array($ownerType, ['module', 'plugin'], true) ? $ownerType : 'module';
	}

	/**
	 * @param mixed $value
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
			if (is_scalar($token)) {
				$token = trim((string) $token);
				if ($token !== '') {
					$tokens[] = $token;
				}
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
	 * @param mixed $value
	 * @return array<int, mixed>
	 */
	private function normalizeChildren(mixed $value): array
	{
		if (! is_array($value)) {
			return [];
		}

		$children = [];

		foreach ($value as $child) {
			$normalizedChild = $this->normalizeChildNode($child);
			if ($normalizedChild !== null) {
				$children[] = $normalizedChild;
			}
		}

		return $children;
	}

	/**
	 * @return array<string, mixed>|string|null
	 */
	private function normalizeChildNode(mixed $child): array|string|null
	{
		if (is_string($child)) {
			$child = trim($child);

			return $child !== '' ? $child : null;
		}

		if (! is_array($child)) {
			return null;
		}

		$resolved = [];

		if (isset($child['key']) && is_scalar($child['key'])) {
			$key = trim((string) $child['key']);
			if ($key !== '') {
				$resolved['key'] = $key;
			}
		}

		if (isset($child['component']) && is_scalar($child['component'])) {
			$component = trim((string) $child['component']);
			if ($component !== '') {
				$resolved['component'] = $component;
			}
		}

		if (isset($child['element']) && is_scalar($child['element'])) {
			$element = trim((string) $child['element']);
			if ($element !== '') {
				$resolved['element'] = $element;
			}
		}

		if (isset($child['tag']) && is_scalar($child['tag'])) {
			$tag = trim((string) $child['tag']);
			if ($tag !== '') {
				$resolved['tag'] = $tag;
			}
		}

		if (isset($child['class'])) {
			$class = $this->normalizeClassValue($child['class']);
			if ($class !== '') {
				$resolved['class'] = $class;
			}
		}

		if (isset($child['style'])) {
			$style = $this->normalizeStyleValue($child['style']);
			if ($style !== '') {
				$resolved['style'] = $style;
			}
		}

		$attributes = $this->normalizeAttributes($child['attributes'] ?? []);
		if ($attributes !== []) {
			$resolved['attributes'] = $attributes;
		}

		if (isset($child['text']) && is_scalar($child['text'])) {
			$text = trim((string) $child['text']);
			if ($text !== '') {
				$resolved['text'] = $text;
			}
		}

		if (isset($child['inner_html']) && is_string($child['inner_html'])) {
			$innerHtml = trim($child['inner_html']);
			if ($innerHtml !== '') {
				$resolved['inner_html'] = $innerHtml;
			}
		}

		$nestedChildren = $this->normalizeChildren($child['children'] ?? []);
		if ($nestedChildren !== []) {
			$resolved['children'] = $nestedChildren;
		}

		return $resolved !== [] ? $resolved : null;
	}
}
