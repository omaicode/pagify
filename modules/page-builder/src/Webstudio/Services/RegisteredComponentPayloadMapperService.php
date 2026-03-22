<?php

namespace Pagify\PageBuilder\Webstudio\Services;

class RegisteredComponentPayloadMapperService
{
	/**
	 * @param array<int, array<string, mixed>> $blocks
	 * @return array<int, array<string, mixed>>
	 */
	public function map(array $blocks): array
	{
		$resolved = [];

		foreach ($blocks as $block) {
			if (! is_array($block)) {
				continue;
			}

			$key = trim((string) ($block['key'] ?? ''));
			if ($key === '') {
				continue;
			}

			$resolved[] = [
				...$block,
				'key' => $key,
				'label' => (string) ($block['label'] ?? $key),
				'icon' => (string) ($block['icon'] ?? '🧩'),
				'category' => (string) ($block['category'] ?? 'Registered Components'),
				'description' => (string) ($block['description'] ?? ''),
				'owner' => (string) ($block['owner'] ?? 'core'),
				'owner_type' => (string) ($block['owner_type'] ?? 'module'),
				'source' => (string) ($block['source'] ?? 'internal'),
				'html_template' => (string) ($block['html_template'] ?? ''),
				'props_schema' => is_array($block['props_schema'] ?? null) ? $block['props_schema'] : [],
				'element' => (string) ($block['element'] ?? ''),
				'tag' => (string) ($block['tag'] ?? ''),
				'class' => (string) ($block['class'] ?? ''),
				'style' => (string) ($block['style'] ?? ''),
				'attributes' => is_array($block['attributes'] ?? null) ? $block['attributes'] : [],
				'text' => (string) ($block['text'] ?? ''),
				'inner_html' => (string) ($block['inner_html'] ?? ''),
				'children' => is_array($block['children'] ?? null) ? array_values($block['children']) : [],
				'dynamic_data' => is_array($block['dynamic_data'] ?? null) ? $block['dynamic_data'] : [],
			];
		}

		return $resolved;
	}
}
