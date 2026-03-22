<?php

namespace Pagify\PageBuilder\Webstudio\Services;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Str;
use Pagify\Core\Services\ModuleRegistry;
use Pagify\Core\Services\PluginManagerService;
use Pagify\PageBuilder\Webstudio\Contracts\CustomComponent;
use Throwable;

class ComponentRegistryAuditService
{
	public function __construct(
		private readonly Container $app,
		private readonly ModuleRegistry $modules,
		private readonly PluginManagerService $plugins,
		private readonly ComponentDefinitionValidator $validator,
	) {
	}

	/**
	 * @return array{ok: bool, checked: int, valid: int, issues: array<int, array<string, string>>}
	 */
	public function auditAll(): array
	{
		$issues = [];
		$checked = 0;
		$valid = 0;

		foreach ($this->modules->all() as $moduleSlug => $module) {
			$result = $this->auditComponentClassList(
				classes: $module['webstudio_components'] ?? [],
				owner: (string) $moduleSlug,
				ownerType: 'module',
			);

			$checked += $result['checked'];
			$valid += $result['valid'];
			$issues = [...$issues, ...$result['issues']];
		}

		foreach ($this->plugins->all() as $plugin) {
			$slug = (string) ($plugin['slug'] ?? 'plugin');
			$rootPath = is_string($plugin['root_path'] ?? null) ? (string) $plugin['root_path'] : '';
			$config = $this->loadConfigArray($rootPath !== '' ? ($rootPath . '/config/plugin.php') : '');

			$result = $this->auditComponentClassList(
				classes: $config['webstudio_components'] ?? [],
				owner: $slug,
				ownerType: 'plugin',
				pluginRootPath: $rootPath,
			);

			$checked += $result['checked'];
			$valid += $result['valid'];
			$issues = [...$issues, ...$result['issues']];
		}

		return [
			'ok' => $issues === [],
			'checked' => $checked,
			'valid' => $valid,
			'issues' => $issues,
		];
	}

	/**
	 * @param mixed $classes
	 * @return array{checked: int, valid: int, issues: array<int, array<string, string>>}
	 */
	private function auditComponentClassList(mixed $classes, string $owner, string $ownerType, ?string $pluginRootPath = null): array
	{
		$issues = [];
		$checked = 0;
		$valid = 0;

		if (! is_array($classes)) {
			return [
				'checked' => 0,
				'valid' => 0,
				'issues' => [[
					'owner' => $owner,
					'owner_type' => $ownerType,
					'class' => '-',
					'error' => 'webstudio_components must be an array of class names.',
				]],
			];
		}

		foreach ($classes as $componentClass) {
			$checked++;

			if (! is_string($componentClass) || trim($componentClass) === '') {
				$issues[] = [
					'owner' => $owner,
					'owner_type' => $ownerType,
					'class' => '-',
					'error' => 'Component class entry must be a non-empty string.',
				];

				continue;
			}

			$componentClass = trim($componentClass);

			if ($ownerType === 'plugin' && is_string($pluginRootPath) && $pluginRootPath !== '') {
				$this->tryAutoloadPluginClass($componentClass, $owner, $pluginRootPath);
			}

			if (! class_exists($componentClass)) {
				$issues[] = [
					'owner' => $owner,
					'owner_type' => $ownerType,
					'class' => $componentClass,
					'error' => 'Class does not exist.',
				];

				continue;
			}

			if (! is_subclass_of($componentClass, CustomComponent::class)) {
				$issues[] = [
					'owner' => $owner,
					'owner_type' => $ownerType,
					'class' => $componentClass,
					'error' => sprintf('Class must implement %s.', CustomComponent::class),
				];

				continue;
			}

			try {
				$component = $this->app->make($componentClass);
			} catch (Throwable $exception) {
				$issues[] = [
					'owner' => $owner,
					'owner_type' => $ownerType,
					'class' => $componentClass,
					'error' => 'Unable to instantiate component: ' . $exception->getMessage(),
				];

				continue;
			}

			$definition = $component->definition();
			if (! is_array($definition)) {
				$issues[] = [
					'owner' => $owner,
					'owner_type' => $ownerType,
					'class' => $componentClass,
					'error' => 'definition() must return an array.',
				];

				continue;
			}

			$definition['owner'] = (string) ($definition['owner'] ?? $owner);
			$definition['owner_type'] = (string) ($definition['owner_type'] ?? $ownerType);
			$normalized = $this->validator->validateAndNormalize($definition, $componentClass);

			if (! is_array($normalized)) {
				$issues[] = [
					'owner' => $owner,
					'owner_type' => $ownerType,
					'class' => $componentClass,
					'error' => 'Definition is invalid after normalization/validation.',
				];

				continue;
			}

			$valid++;
		}

		return [
			'checked' => $checked,
			'valid' => $valid,
			'issues' => $issues,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function loadConfigArray(string $path): array
	{
		if ($path === '' || ! is_file($path)) {
			return [];
		}

		$payload = require $path;

		return is_array($payload) ? $payload : [];
	}

	private function tryAutoloadPluginClass(string $componentClass, string $pluginSlug, string $pluginRootPath): void
	{
		if (class_exists($componentClass)) {
			return;
		}

		$namespacePrefix = 'Plugins\\' . Str::of($pluginSlug)
			->replace(['-', '_'], ' ')
			->title()
			->replace(' ', '')
			->toString() . '\\';

		if (! str_starts_with($componentClass, $namespacePrefix)) {
			return;
		}

		$relativePath = str_replace('\\', '/', substr($componentClass, strlen($namespacePrefix))) . '.php';
		$filePath = rtrim($pluginRootPath, '/') . '/src/' . $relativePath;

		if (is_file($filePath)) {
			require_once $filePath;
		}
	}
}
