<?php

namespace Pagify\PageBuilder\Services;

use Pagify\Core\Services\ModuleRegistry;
use Pagify\Core\Services\PluginManagerService;

class WebstudioComponentDefinitionDiscoveryService
{
	public function __construct(
		private readonly ModuleRegistry $modules,
		private readonly PluginManagerService $plugins,
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

			$moduleRoot = base_path('modules/' . $moduleSlug);
			$definitions = $this->loadDefinitionsFromPaths([
				$moduleRoot . '/config/webstudio-components.php',
				$moduleRoot . '/webstudio-components.php',
			]);

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

			$definitions = $this->loadDefinitionsFromPaths([
				$rootPath . '/config/webstudio-components.php',
				$rootPath . '/webstudio-components.php',
			]);

			$slug = (string) ($plugin['slug'] ?? 'plugin');

			foreach ($definitions as $definition) {
				$definition['owner'] = (string) ($definition['owner'] ?? $slug);
				$definition['owner_type'] = (string) ($definition['owner_type'] ?? 'plugin');
				$resolved[] = $definition;
			}
		}

		return $resolved;
	}

	/**
	 * @param array<int, string> $paths
	 * @return array<int, array<string, mixed>>
	 */
	private function loadDefinitionsFromPaths(array $paths): array
	{
		$resolved = [];

		foreach ($paths as $path) {
			if (! is_file($path)) {
				continue;
			}

			$payload = require $path;
			if (! is_array($payload)) {
				continue;
			}

			foreach ($payload as $item) {
				if (is_array($item)) {
					$resolved[] = $item;
				}
			}
		}

		return $resolved;
	}
}
