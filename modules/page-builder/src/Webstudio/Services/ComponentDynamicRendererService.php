<?php

namespace Pagify\PageBuilder\Webstudio\Services;

use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Webstudio\Helpers\ComponentDynamicContextHelper;

class ComponentDynamicRendererService
{
	public function __construct(
		private readonly ComponentDynamicContextHelper $contextHelper,
	) {
	}

	/**
	 * @param array<int, array<string, mixed>> $blocks
	 * @param array<string, mixed> $runtimeState
	 * @return array<int, array<string, mixed>>
	 */
	public function render(array $blocks, ?Page $page = null, array $runtimeState = []): array
	{
		$baseContext = $this->contextHelper->buildContext($page, $runtimeState);

		$resolved = [];

		foreach ($blocks as $block) {
			if (! is_array($block)) {
				continue;
			}

			$resolved[] = $this->renderBlock($block, $baseContext);
		}

		return $resolved;
	}

	/**
	 * @param array<string, mixed> $block
	 * @param array<string, mixed> $baseContext
	 * @return array<string, mixed>
	 */
	private function renderBlock(array $block, array $baseContext): array
	{
		$dynamicData = isset($block['dynamic_data']) && is_array($block['dynamic_data'])
			? $this->renderValue($block['dynamic_data'], $baseContext)
			: [];

		$context = $this->contextHelper->buildContext(
			page: null,
			runtimeState: (array) ($baseContext['runtime'] ?? []),
			dynamicData: is_array($dynamicData) ? $dynamicData : [],
		);

		if (isset($baseContext['page']) && is_array($baseContext['page'])) {
			$context['page'] = $baseContext['page'];
		}

		if (isset($baseContext['project']) && is_array($baseContext['project'])) {
			$context['project'] = $baseContext['project'];
		}

		$block['label'] = $this->renderString((string) ($block['label'] ?? ''), $context);
		$block['description'] = $this->renderString((string) ($block['description'] ?? ''), $context);
		$block['icon'] = $this->renderString((string) ($block['icon'] ?? ''), $context);
		$block['category'] = $this->renderString((string) ($block['category'] ?? ''), $context);
		$block['owner'] = $this->renderString((string) ($block['owner'] ?? ''), $context);
		$block['html_template'] = $this->renderString((string) ($block['html_template'] ?? ''), $context);
		$block['element'] = $this->renderString((string) ($block['element'] ?? ''), $context);
		$block['tag'] = $this->renderString((string) ($block['tag'] ?? ''), $context);
		$block['class'] = $this->renderString((string) ($block['class'] ?? ''), $context);
		$block['style'] = $this->renderString((string) ($block['style'] ?? ''), $context);
		$block['text'] = $this->renderString((string) ($block['text'] ?? ''), $context);
		$block['inner_html'] = $this->renderString((string) ($block['inner_html'] ?? ''), $context);
		$block['attributes'] = is_array($block['attributes'] ?? null)
			? $this->renderValue($block['attributes'], $context)
			: [];
		$block['children'] = is_array($block['children'] ?? null)
			? $this->renderValue($block['children'], $context)
			: [];
		$block['dynamic_data'] = is_array($dynamicData) ? $dynamicData : [];

		return $block;
	}

	/**
	 * @param mixed $value
	 * @param array<string, mixed> $context
	 * @return mixed
	 */
	private function renderValue(mixed $value, array $context): mixed
	{
		if (is_string($value)) {
			return $this->renderString($value, $context);
		}

		if (! is_array($value)) {
			return $value;
		}

		$resolved = [];
		foreach ($value as $key => $item) {
			$resolved[$key] = $this->renderValue($item, $context);
		}

		return $resolved;
	}

	/**
	 * @param array<string, mixed> $context
	 */
	private function renderString(string $value, array $context): string
	{
		if ($value === '' || str_contains($value, '{{') === false) {
			return $value;
		}

		return (string) preg_replace_callback('/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/', function (array $matches) use ($context): string {
			$path = (string) ($matches[1] ?? '');
			if ($path === '') {
				return '';
			}

			$resolved = data_get($context, $path);
			if (is_scalar($resolved)) {
				return (string) $resolved;
			}

			return '';
		}, $value);
	}

}

