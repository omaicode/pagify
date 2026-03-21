<?php

namespace Pagify\PageBuilder\Services;

use Pagify\Core\Services\EventBus;
use Pagify\Core\Services\PluginManagerService;

class BlockRegistryService
{
	public function __construct(
		private readonly PluginManagerService $plugins,
		private readonly EventBus $eventBus,
	)
	{
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function all(): array
	{
		$internal = $this->normalizeInternalBlocks((array) config('page-builder.internal_blocks', []));
		$external = $this->normalizePluginBlocks((array) ($this->plugins->extensionPoints()['blocks'] ?? []));
		$hooked = $this->normalizeHookBlocks($this->eventBus->emitHook('page-builder.webstudio.components'));

		$merged = [];
		foreach ([...$internal, ...$external, ...$hooked] as $block) {
			$key = (string) ($block['key'] ?? '');

			if ($key === '') {
				continue;
			}

			$merged[$key] = $block;
		}

		return array_values($merged);
	}

	/**
	 * @param array<int, array<string, mixed>> $blocks
	 * @return array<int, array<string, mixed>>
	 */
	private function normalizeInternalBlocks(array $blocks): array
	{
		return array_values(array_filter(array_map(static function (mixed $item): ?array {
			if (! is_array($item)) {
				return null;
			}

			$key = (string) ($item['key'] ?? '');
			if ($key === '') {
				return null;
			}

			$htmlTemplate = self::resolveHtmlTemplate($item, $key);

			return [
				'key' => $key,
				'label' => (string) ($item['label'] ?? $key),
				'icon' => (string) ($item['icon'] ?? '🧩'),
				'category' => (string) ($item['category'] ?? 'General'),
				'owner' => 'core',
				'owner_type' => 'module',
				'description' => (string) ($item['description'] ?? ''),
				'html_template' => $htmlTemplate,
				'props_schema' => (array) ($item['props_schema'] ?? []),
				...self::extractDefinitionFields($item),
				'source' => 'internal',
			];
		}, $blocks)));
	}

	/**
	 * @param array<int, array<string, mixed>> $items
	 * @return array<int, array<string, mixed>>
	 */
	private function normalizePluginBlocks(array $items): array
	{
		$resolved = [];

		foreach ($items as $item) {
			$value = $item['value'] ?? null;
			if (! is_array($value)) {
				continue;
			}

			$key = (string) ($value['key'] ?? $value['type'] ?? '');
			if ($key === '') {
				continue;
			}

			$htmlTemplate = $this->resolveHtmlTemplate($value, $key);

			$resolved[] = [
				'key' => $key,
				'label' => (string) ($value['label'] ?? $key),
				'icon' => (string) ($value['icon'] ?? '🧩'),
				'category' => (string) ($value['category'] ?? 'Plugin Blocks'),
				'owner' => (string) ($item['plugin'] ?? 'plugin'),
				'owner_type' => 'plugin',
				'description' => (string) ($value['description'] ?? ''),
				'html_template' => $htmlTemplate,
				'props_schema' => (array) ($value['props_schema'] ?? []),
				...self::extractDefinitionFields($value),
				'source' => 'plugin',
				'plugin' => (string) ($item['plugin'] ?? ''),
			];
		}

		return $resolved;
	}

	/**
	 * @param array<int, mixed> $hookResults
	 * @return array<int, array<string, mixed>>
	 */
	private function normalizeHookBlocks(array $hookResults): array
	{
		$resolved = [];

		foreach ($hookResults as $hookResult) {
			if (! is_array($hookResult)) {
				continue;
			}

			foreach ($hookResult as $item) {
				if (! is_array($item)) {
					continue;
				}

				$owner = trim((string) ($item['owner'] ?? 'unknown'));
				if ($owner === '') {
					$owner = 'unknown';
				}

				$key = trim((string) ($item['key'] ?? $item['type'] ?? ''));
				if ($key === '') {
					continue;
				}

				if (! str_contains($key, ':')) {
					$key = $owner . ':' . $key;
				}

				$htmlTemplate = $this->resolveHtmlTemplate($item, $key);

				$resolved[] = [
					'key' => $key,
					'label' => (string) ($item['label'] ?? $key),
					'icon' => (string) ($item['icon'] ?? '🧩'),
					'category' => (string) ($item['category'] ?? 'Registered Components'),
					'owner' => $owner,
					'owner_type' => (string) ($item['owner_type'] ?? 'module'),
					'description' => (string) ($item['description'] ?? ''),
					'html_template' => $htmlTemplate,
					'props_schema' => (array) ($item['props_schema'] ?? []),
					...self::extractDefinitionFields($item),
					'source' => 'hook',
				];
			}
		}

		return $resolved;
	}

	/**
	 * @param array<string, mixed> $item
	 * @return array<string, mixed>
	 */
	private static function extractDefinitionFields(array $item): array
	{
		return [
			'element' => trim((string) ($item['element'] ?? '')),
			'tag' => trim((string) ($item['tag'] ?? '')),
			'class' => self::normalizeClassValue($item['class'] ?? null),
			'style' => self::normalizeStyleValue($item['style'] ?? null),
			'attributes' => self::normalizeDeclaredAttributes($item['attributes'] ?? []),
			'text' => isset($item['text']) && is_scalar($item['text']) ? trim((string) $item['text']) : '',
			'inner_html' => is_string($item['inner_html'] ?? null) ? (string) $item['inner_html'] : '',
			'children' => self::normalizeChildren($item['children'] ?? []),
		];
	}

	/**
	 * @param mixed $value
	 * @return array<int, mixed>
	 */
	private static function normalizeChildren(mixed $value): array
	{
		if (! is_array($value)) {
			return [];
		}

		$children = [];

		foreach ($value as $child) {
			$normalizedChild = self::normalizeChildNode($child);
			if ($normalizedChild !== null) {
				$children[] = $normalizedChild;
			}
		}

		return $children;
	}

	/**
	 * @return array<string, mixed>|string|null
	 */
	private static function normalizeChildNode(mixed $child): array|string|null
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
			$class = self::normalizeClassValue($child['class']);
			if ($class !== '') {
				$resolved['class'] = $class;
			}
		}

		if (isset($child['style'])) {
			$style = self::normalizeStyleValue($child['style']);
			if ($style !== '') {
				$resolved['style'] = $style;
			}
		}

		$attributes = self::normalizeDeclaredAttributes($child['attributes'] ?? []);
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

		$nestedChildren = self::normalizeChildren($child['children'] ?? []);
		if ($nestedChildren !== []) {
			$resolved['children'] = $nestedChildren;
		}

		return $resolved !== [] ? $resolved : null;
	}

	/**
	 * @param array<string, mixed> $item
	 */
	private static function resolveHtmlTemplate(array $item, string $key): string
	{
		$htmlTemplate = trim((string) ($item['html_template'] ?? ''));
		if ($htmlTemplate !== '') {
			return $htmlTemplate;
		}

		$tag = strtolower(trim((string) ($item['element'] ?? $item['tag'] ?? '')));
		if ($tag !== '' && preg_match('/^[a-z][a-z0-9:-]*$/', $tag) === 1) {
			$attributes = self::normalizeAttributes($item);
			$attributeString = self::attributesToString($attributes);

			$innerHtml = $item['inner_html'] ?? null;
			if (is_string($innerHtml) && trim($innerHtml) !== '') {
				return sprintf('<%1$s%2$s>%3$s</%1$s>', $tag, $attributeString, $innerHtml);
			}

			$text = (string) ($item['text'] ?? $item['label'] ?? $key);
			return sprintf('<%1$s%2$s>%3$s</%1$s>', $tag, $attributeString, e($text));
		}

		$legacyText = (string) (($item['props_schema']['text'] ?? $item['label'] ?? $key));

		return '<section class="pbx-section"><h3 class="pbx-subheading">' . e($legacyText) . '</h3></section>';
	}

	/**
	 * @param array<string, mixed> $item
	 * @return array<string, string>
	 */
	private static function normalizeAttributes(array $item): array
	{
		$attributes = [];

		$attributes = self::normalizeDeclaredAttributes($item['attributes'] ?? []);

		$classValue = self::normalizeClassValue($item['class'] ?? null);

		if (is_string($classValue) && trim($classValue) !== '') {
			$attributes['class'] = trim($classValue);
		}

		$styleValue = self::normalizeStyleValue($item['style'] ?? null);

		if (is_string($styleValue) && trim($styleValue) !== '') {
			$styleValue = trim($styleValue);
			$attributes['style'] = str_ends_with($styleValue, ';') ? $styleValue : ($styleValue . ';');
		}

		return $attributes;
	}

	/**
	 * @param mixed $value
	 * @return array<string, string>
	 */
	private static function normalizeDeclaredAttributes(mixed $value): array
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

	private static function normalizeClassValue(mixed $value): string
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

	private static function normalizeStyleValue(mixed $value): string
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
	 * @param array<string, string> $attributes
	 */
	private static function attributesToString(array $attributes): string
	{
		if ($attributes === []) {
			return '';
		}

		$tokens = [];

		foreach ($attributes as $name => $value) {
			$tokens[] = sprintf('%s="%s"', $name, e($value));
		}

		return ' ' . implode(' ', $tokens);
	}
}
