<?php

namespace Pagify\PageBuilder\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Pagify\Core\Services\FrontendThemeManagerService;
use Pagify\Core\Wsre\Resolvers\PageBuilderInterfaceResolver;
use Pagify\PageBuilder\Models\Page;

class PageWsrePublisherService
{
	public function __construct(
		private readonly FrontendThemeManagerService $themeManager,
		private readonly Filesystem $files,
	) {
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	public function publish(Page $page, array $payload = []): string
	{
		$themeSlug = $this->themeManager->activeThemeForCurrentSite();
		$themesBasePath = trim((string) config('core.frontend_ui.themes_base_path', 'themes/main'), '/');
		$themePath = base_path($themesBasePath . '/' . $themeSlug);
		$pagesPath = $themePath . '/pages';

		$this->files->ensureDirectoryExists($pagesPath);

		$pageName = $this->resolvePageName($page);
		$filePath = $pagesPath . '/' . $pageName . '.json';
		$this->cleanupStalePublishedFiles($pagesPath, $filePath, (int) $page->id);

		$document = $this->buildWsreDocument($page, $payload);
		$encoded = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		$this->files->put($filePath, (is_string($encoded) ? $encoded : '{}') . "\n");

		return $filePath;
	}

	private function cleanupStalePublishedFiles(string $pagesPath, string $currentFilePath, int $pageId): void
	{
		$publishedFiles = $this->files->glob($pagesPath . '/*.json');
		if (! is_array($publishedFiles) || $publishedFiles === []) {
			return;
		}

		$currentRealPath = realpath($currentFilePath) ?: $currentFilePath;

		foreach ($publishedFiles as $publishedFile) {
			$fileRealPath = realpath($publishedFile) ?: $publishedFile;
			if ($fileRealPath === $currentRealPath || ! $this->files->exists($publishedFile)) {
				continue;
			}

			$decoded = json_decode((string) $this->files->get($publishedFile), true);
			if (! is_array($decoded)) {
				continue;
			}

			$publishedPageId = data_get($decoded, 'page.id');
			if ((int) $publishedPageId !== $pageId) {
				continue;
			}

			$this->files->delete($publishedFile);
		}
	}

	private function resolvePageName(Page $page): string
	{
		$slug = trim((string) $page->slug);

		if ($slug === '' || $slug === '/') {
			return 'home';
		}

		$normalized = preg_replace('/[^a-z0-9_-]/i', '-', $slug);
		$normalized = is_string($normalized) ? trim($normalized, '-') : '';

		return $normalized !== '' ? strtolower($normalized) : 'page-' . (string) $page->id;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function buildWsreDocument(Page $page, array $payload): array
	{
		$interface = $this->normalizeInterfacePayload(
			is_array($payload['interface'] ?? null) ? (array) $payload['interface'] : []
		);

		$seo = (array) ($page->seo_meta_json ?? []);
		$pageTitle = trim((string) ($seo['title'] ?? $page->title));
		$description = trim((string) ($seo['description'] ?? ''));

		$head = [];
		if ($pageTitle !== '') {
			$head[] = [
				'tag' => 'title',
				'text' => $pageTitle,
			];
		}
		if ($description !== '') {
			$head[] = [
				'tag' => 'meta',
				'attrs' => [
					'name' => 'description',
					'content' => $description,
				],
			];
		}

		$rootInstanceId = trim((string) Arr::get($payload, 'page.rootInstanceId', ''));
		if ($rootInstanceId === '') {
			$rootInstanceId = 'root-' . (string) $page->id;
		}

		return [
			'version' => 1,
			'engine' => 'wsre',
			'page' => [
				'id' => (int) $page->id,
				'slug' => (string) $page->slug,
			],
			'head' => $head,
			'body' => [[
				'dynamic' => [
					'resolver' => PageBuilderInterfaceResolver::KEY,
					'params' => [
						'page_id' => (int) $page->id,
						'root_instance_id' => $rootInstanceId,
						'interface' => $interface,
					],
				],
			]],
		];
	}

	/**
	 * @param array<string, mixed> $interface
	 * @return array<string, mixed>
	 */
	private function normalizeInterfacePayload(array $interface): array
	{
		$instances = [];
		foreach ((array) ($interface['instances'] ?? []) as $instance) {
			if (! is_array($instance)) {
				continue;
			}
			$instances[] = [
				'id' => (string) ($instance['id'] ?? ''),
				'component' => (string) ($instance['component'] ?? ''),
				'tag' => isset($instance['tag']) ? (string) $instance['tag'] : null,
				'children' => is_array($instance['children'] ?? null) ? (array) $instance['children'] : [],
			];
		}

		$props = [];
		foreach ((array) ($interface['props'] ?? []) as $prop) {
			if (! is_array($prop)) {
				continue;
			}
			$props[] = [
				'instanceId' => (string) ($prop['instanceId'] ?? ''),
				'name' => (string) ($prop['name'] ?? ''),
				'type' => (string) ($prop['type'] ?? ''),
				'value' => $prop['value'] ?? null,
			];
		}

		$styles = [];
		foreach ((array) ($interface['styles'] ?? []) as $style) {
			if (! is_array($style)) {
				continue;
			}
			$styles[] = [
				'styleSourceId' => (string) ($style['styleSourceId'] ?? ''),
				'breakpointId' => (string) ($style['breakpointId'] ?? ''),
				'property' => (string) ($style['property'] ?? ''),
				'value' => $style['value'] ?? null,
			];
		}

		$styleSourceSelections = [];
		foreach ((array) ($interface['styleSourceSelections'] ?? []) as $selection) {
			if (! is_array($selection)) {
				continue;
			}
			$styleSourceSelections[] = [
				'instanceId' => (string) ($selection['instanceId'] ?? ''),
				'values' => is_array($selection['values'] ?? null) ? array_values((array) $selection['values']) : [],
			];
		}

		$breakpoints = [];
		foreach ((array) ($interface['breakpoints'] ?? []) as $breakpoint) {
			if (! is_array($breakpoint)) {
				continue;
			}
			$breakpoints[] = [
				'id' => (string) ($breakpoint['id'] ?? ''),
				'maxWidth' => isset($breakpoint['maxWidth']) ? (int) $breakpoint['maxWidth'] : null,
			];
		}

		return [
			'instances' => $instances,
			'props' => $props,
			'styles' => $styles,
			'styleSourceSelections' => $styleSourceSelections,
			'breakpoints' => $breakpoints,
			'dataSources' => $this->normalizeDataSources((array) ($interface['dataSources'] ?? [])),
			'resources' => $this->normalizeResources((array) ($interface['resources'] ?? [])),
			'variableValues' => is_array($interface['variableValues'] ?? null) ? (array) $interface['variableValues'] : [],
		];
	}

	/**
	 * @param array<int, mixed> $dataSources
	 * @return array<int, array<string, mixed>>
	 */
	private function normalizeDataSources(array $dataSources): array
	{
		$normalized = [];
		foreach ($dataSources as $item) {
			if (! is_array($item)) {
				continue;
			}
			$normalized[] = [
				'id' => (string) ($item['id'] ?? ''),
				'type' => (string) ($item['type'] ?? ''),
				'name' => (string) ($item['name'] ?? ''),
				'scopeInstanceId' => isset($item['scopeInstanceId']) ? (string) $item['scopeInstanceId'] : null,
				'value' => is_array($item['value'] ?? null) ? (array) $item['value'] : null,
				'resourceId' => isset($item['resourceId']) ? (string) $item['resourceId'] : null,
			];
		}

		return $normalized;
	}

	/**
	 * @param array<int, mixed> $resources
	 * @return array<int, array<string, mixed>>
	 */
	private function normalizeResources(array $resources): array
	{
		$normalized = [];
		foreach ($resources as $item) {
			if (! is_array($item)) {
				continue;
			}
			$normalized[] = [
				'id' => (string) ($item['id'] ?? ''),
				'name' => (string) ($item['name'] ?? ''),
				'method' => (string) ($item['method'] ?? ''),
				'url' => (string) ($item['url'] ?? ''),
			];
		}

		return $normalized;
	}
}
