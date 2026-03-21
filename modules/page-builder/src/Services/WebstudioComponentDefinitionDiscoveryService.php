<?php

namespace Pagify\PageBuilder\Services;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Pagify\Core\Services\ModuleRegistry;
use Pagify\Core\Services\PluginManagerService;
use Pagify\PageBuilder\Contracts\WebstudioCustomComponent;
use Throwable;

class WebstudioComponentDefinitionDiscoveryService
{
	public function __construct(
		private readonly Container $app,
		private readonly ModuleRegistry $modules,
		private readonly PluginManagerService $plugins,
		private readonly WebstudioComponentDefinitionValidator $validator,
	) {
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function discover(): array
	{
		$definitions = [];

		foreach ($this->discoverModuleDefinitions() as $definition) {
			$definitions[] = $definition;
		}

		foreach ($this->discoverPluginDefinitions() as $definition) {
			$definitions[] = $definition;
		}

		return $definitions;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function discoverModuleDefinitions(): array
	{
		$resolved = [];

		foreach ($this->modules->all() as $moduleSlug => $module) {
			if (! ((bool) ($module['enabled'] ?? false))) {
				continue;
			}

			$definitions = $this->resolveDefinitionsFromComponentClasses(
				classes: $module['webstudio_components'] ?? [],
				owner: (string) $moduleSlug,
				ownerType: 'module',
			);

			foreach ($definitions as $definition) {
				$definition['owner'] = (string) ($definition['owner'] ?? $moduleSlug);
				$definition['owner_type'] = (string) ($definition['owner_type'] ?? 'module');
				$resolved[] = $definition;
			}
		}

		return $resolved;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function discoverPluginDefinitions(): array
	{
		$resolved = [];

		foreach ($this->plugins->all() as $plugin) {
			if (! ((bool) ($plugin['enabled'] ?? false)) || ! ((bool) ($plugin['is_compatible'] ?? false))) {
				continue;
			}

			$rootPath = is_string($plugin['root_path'] ?? null) ? (string) $plugin['root_path'] : '';
			if ($rootPath === '') {
				continue;
			}

			$slug = (string) ($plugin['slug'] ?? 'plugin');
			$config = $this->loadConfigArray($rootPath . '/config/plugin.php');

			$definitions = $this->resolveDefinitionsFromComponentClasses(
				classes: $config['webstudio_components'] ?? [],
				owner: $slug,
				ownerType: 'plugin',
				pluginRootPath: $rootPath,
			);

			foreach ($definitions as $definition) {
				$definition['owner'] = (string) ($definition['owner'] ?? $slug);
				$definition['owner_type'] = (string) ($definition['owner_type'] ?? 'plugin');
				$resolved[] = $definition;
			}
		}

		return $resolved;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function loadConfigArray(string $path): array
	{
		if (! is_file($path)) {
			return [];
		}

		$payload = require $path;

		return is_array($payload) ? $payload : [];
	}

	/**
	 * @param mixed $classes
	 * @return array<int, array<string, mixed>>
	 */
	private function resolveDefinitionsFromComponentClasses(mixed $classes, string $owner, string $ownerType, ?string $pluginRootPath = null): array
	{
		if (! is_array($classes)) {
			return [];
		}

		$resolved = [];

		foreach ($classes as $componentClass) {
			if (! is_string($componentClass) || trim($componentClass) === '') {
				continue;
			}

			$componentClass = trim($componentClass);

			if ($ownerType === 'plugin' && is_string($pluginRootPath) && $pluginRootPath !== '') {
				$this->tryAutoloadPluginClass($componentClass, $owner, $pluginRootPath);
			}

			if (! class_exists($componentClass)) {
				Log::warning('Webstudio custom component class was not found and has been skipped.', [
					'class' => $componentClass,
					'owner' => $owner,
					'owner_type' => $ownerType,
				]);

				continue;
			}

			if (! is_subclass_of($componentClass, WebstudioCustomComponent::class)) {
				Log::warning('Webstudio custom component class must implement interface and has been skipped.', [
					'class' => $componentClass,
					'owner' => $owner,
					'owner_type' => $ownerType,
					'interface' => WebstudioCustomComponent::class,
				]);

				continue;
			}

			try {
				$component = $this->app->make($componentClass);
			} catch (Throwable $exception) {
				Log::warning('Webstudio custom component could not be instantiated and has been skipped.', [
					'class' => $componentClass,
					'owner' => $owner,
					'owner_type' => $ownerType,
					'error' => $exception->getMessage(),
				]);

				continue;
			}

			$definition = $this->resolveComponentDefinition($component, $componentClass);
			if (! is_array($definition)) {
				continue;
			}

			$definition['owner'] = (string) ($definition['owner'] ?? $owner);
			$definition['owner_type'] = (string) ($definition['owner_type'] ?? $ownerType);

			$normalized = $this->validator->validateAndNormalize($definition, $componentClass);
			if ($normalized === null) {
				continue;
			}

			$normalized['owner'] = (string) ($normalized['owner'] !== '' ? $normalized['owner'] : $owner);
			$normalized['owner_type'] = (string) ($normalized['owner_type'] ?? $ownerType);

			$resolved[] = $normalized;
		}

		return $resolved;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function resolveComponentDefinition(mixed $component, string $componentClass): ?array
	{
		if (! $component instanceof WebstudioCustomComponent) {
			Log::warning('Webstudio custom component class must implement interface and has been skipped.', [
				'class' => $componentClass,
				'interface' => WebstudioCustomComponent::class,
			]);

			return null;
		}

		$definition = $component->definition();

		if (! is_array($definition)) {
			Log::warning('Webstudio custom component returned invalid definition payload.', [
				'class' => $componentClass,
			]);

			return null;
		}

		return $definition;
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
