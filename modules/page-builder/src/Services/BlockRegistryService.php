<?php

namespace Pagify\PageBuilder\Services;

use Pagify\Core\Services\PluginManagerService;

class BlockRegistryService
{
	public function __construct(private readonly PluginManagerService $plugins)
	{
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function all(): array
	{
		$internal = $this->normalizeInternalBlocks((array) config('page-builder.internal_blocks', []));
		$external = $this->normalizePluginBlocks((array) ($this->plugins->extensionPoints()['blocks'] ?? []));

		$merged = [];
		foreach ([...$internal, ...$external] as $block) {
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

			$htmlTemplate = (string) ($item['html_template'] ?? '');
			if ($htmlTemplate === '') {
				$legacyText = (string) (($item['props_schema']['text'] ?? $item['label'] ?? $key));
				$htmlTemplate = '<section class="pbx-section"><h3 class="pbx-subheading">' . e($legacyText) . '</h3></section>';
			}

			return [
				'key' => $key,
				'label' => (string) ($item['label'] ?? $key),
				'icon' => (string) ($item['icon'] ?? '🧩'),
				'category' => (string) ($item['category'] ?? 'General'),
				'description' => (string) ($item['description'] ?? ''),
				'html_template' => $htmlTemplate,
				'props_schema' => (array) ($item['props_schema'] ?? []),
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

			$htmlTemplate = (string) ($value['html_template'] ?? '');
			if ($htmlTemplate === '') {
				$legacyText = (string) (($value['props_schema']['text'] ?? $value['label'] ?? $key));
				$htmlTemplate = '<section class="pbx-section"><h3 class="pbx-subheading">' . e($legacyText) . '</h3></section>';
			}

			$resolved[] = [
				'key' => $key,
				'label' => (string) ($value['label'] ?? $key),
				'icon' => (string) ($value['icon'] ?? '🧩'),
				'category' => (string) ($value['category'] ?? 'Plugin Blocks'),
				'description' => (string) ($value['description'] ?? ''),
				'html_template' => $htmlTemplate,
				'props_schema' => (array) ($value['props_schema'] ?? []),
				'source' => 'plugin',
				'plugin' => (string) ($item['plugin'] ?? ''),
			];
		}

		return $resolved;
	}
}
