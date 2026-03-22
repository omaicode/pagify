<?php

namespace Pagify\PageBuilder\Webstudio\Helpers;

use Pagify\PageBuilder\Models\Page;

class ComponentDynamicContextHelper
{
	/**
	 * @return array<int, string>
	 */
	public function supportedPlaceholderRoots(): array
	{
		return ['page', 'project', 'runtime', 'dynamic', 'now'];
	}

	/**
	 * @return array<string, string>
	 */
	public function supportedPlaceholderExamples(): array
	{
		return [
			'page.id' => 'Page id in editor context',
			'page.title' => 'Page title in editor context',
			'page.slug' => 'Page slug in editor context',
			'page.status' => 'Page status in editor context',
			'project.id' => 'Project id (mapped from current page)',
			'project.status' => 'Project/marketplace status from runtime state',
			'runtime.publisherHost' => 'Publisher host in runtime state',
			'dynamic.{key}' => 'Custom dynamic value from dynamic_data',
			'now' => 'Current ISO datetime at render time',
		];
	}

	/**
	 * @param array<string, mixed> $runtimeState
	 * @param array<string, mixed> $dynamicData
	 * @return array<string, mixed>
	 */
	public function buildContext(?Page $page, array $runtimeState, array $dynamicData = []): array
	{
		$pageContext = $page instanceof Page
			? [
				'id' => (string) $page->id,
				'title' => (string) $page->title,
				'slug' => (string) $page->slug,
				'status' => (string) $page->status,
				'site_id' => (string) $page->site_id,
				'published_at' => $page->published_at?->toIso8601String(),
				'created_at' => $page->created_at?->toIso8601String(),
				'updated_at' => $page->updated_at?->toIso8601String(),
			]
			: [];

		$projectContext = [
			'id' => $page instanceof Page ? (string) $page->id : '',
			'status' => (string) ($runtimeState['marketplaceApprovalStatus'] ?? ''),
		];

		return [
			'page' => $pageContext,
			'project' => $projectContext,
			'runtime' => $runtimeState,
			'dynamic' => $dynamicData,
			...$dynamicData,
			'now' => now()->toIso8601String(),
		];
	}

	/**
	 * @param array<string, mixed> $definition
	 * @return array<int, string>
	 */
	public function validateDefinitionPlaceholders(array $definition): array
	{
		$dynamicData = is_array($definition['dynamic_data'] ?? null)
			? (array) $definition['dynamic_data']
			: [];

		$dynamicPaths = $this->collectDynamicDataPaths($dynamicData);
		$errors = [];
		$this->collectPlaceholderValidationErrors($definition, $dynamicPaths, '$', $errors);

		return array_values(array_unique($errors));
	}

	/**
	 * @param array<string, mixed> $dynamicData
	 * @return array<int, string>
	 */
	public function collectDynamicDataPaths(array $dynamicData): array
	{
		$paths = [];
		$this->collectPaths($dynamicData, '', $paths);

		return array_values(array_unique($paths));
	}

	/**
	 * @param array<int, string> $dynamicPaths
	 * @param array<int, string> $errors
	 */
	private function collectPlaceholderValidationErrors(mixed $value, array $dynamicPaths, string $location, array &$errors): void
	{
		if (is_string($value)) {
			$this->validatePlaceholderString($value, $dynamicPaths, $location, $errors);

			return;
		}

		if (! is_array($value)) {
			return;
		}

		foreach ($value as $key => $item) {
			$nextLocation = is_string($key)
				? $location . '.' . $key
				: $location . '[' . $key . ']';
			$this->collectPlaceholderValidationErrors($item, $dynamicPaths, $nextLocation, $errors);
		}
	}

	/**
	 * @param array<int, string> $dynamicPaths
	 * @param array<int, string> $errors
	 */
	private function validatePlaceholderString(string $value, array $dynamicPaths, string $location, array &$errors): void
	{
		if ($value === '' || str_contains($value, '{{') === false) {
			return;
		}

		$mustacheCount = substr_count($value, '{{');
		$matchedCount = preg_match_all('/\{\{.*?\}\}/', $value, $allMatches);

		if ($mustacheCount !== (int) $matchedCount) {
			$errors[] = sprintf('%s: malformed placeholder syntax.', $location);

			return;
		}

		foreach ($allMatches[0] ?? [] as $rawToken) {
			$token = trim(str_replace(['{{', '}}'], '', (string) $rawToken));
			if ($token === '' || preg_match('/^[A-Za-z0-9_.-]+$/', $token) !== 1) {
				$errors[] = sprintf('%s: invalid placeholder "%s".', $location, $rawToken);

				continue;
			}

			$root = explode('.', $token, 2)[0] ?? '';
			if (! in_array($root, $this->supportedPlaceholderRoots(), true)) {
				$errors[] = sprintf('%s: unsupported placeholder root "%s" in "%s".', $location, $root, $token);

				continue;
			}

			if ($root === 'dynamic') {
				if ($token === 'dynamic') {
					$errors[] = sprintf('%s: placeholder "dynamic" must reference a concrete key.', $location);

					continue;
				}

				$dynamicPath = substr($token, strlen('dynamic.'));
				if (! in_array($dynamicPath, $dynamicPaths, true)) {
					$errors[] = sprintf('%s: unknown dynamic placeholder "%s".', $location, $token);
				}
			}
		}
	}

	/**
	 * @param array<int, string> $paths
	 */
	private function collectPaths(mixed $value, string $prefix, array &$paths): void
	{
		if (! is_array($value)) {
			if ($prefix !== '') {
				$paths[] = $prefix;
			}

			return;
		}

		if ($prefix !== '') {
			$paths[] = $prefix;
		}

		foreach ($value as $key => $item) {
			if (! is_string($key) || trim($key) === '') {
				continue;
			}

			$nextPrefix = $prefix === '' ? $key : $prefix . '.' . $key;
			$this->collectPaths($item, $nextPrefix, $paths);
		}
	}
}
