<?php

namespace Modules\Core\Services;

class ModuleRegistry
{
    /**
     * @param array<string, array<string, mixed>> $modules
     * @param array<string, bool> $runtimeStates
     */
    public function __construct(
        private readonly array $modules,
        private readonly ?ModuleStateService $moduleStateService = null,
        private readonly array $runtimeStates = [],
        /** @var array<string, array<int, array<string, mixed>>> */
        private readonly array $moduleMenus = [],
    )
    {
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $resolved = $this->modules;
        $overrides = $this->runtimeStates !== []
            ? $this->runtimeStates
            : ($this->moduleStateService?->states() ?? []);

        foreach ($overrides as $module => $enabled) {
            if (! isset($resolved[$module])) {
                continue;
            }

            $resolved[$module]['enabled'] = (bool) $enabled;
        }

        return $resolved;
    }

    public function has(string $module): bool
    {
        return isset($this->all()[$module]);
    }

    public function enabled(string $module): bool
    {
        if (! $this->has($module)) {
            return false;
        }

        return (bool) ($this->all()[$module]['enabled'] ?? false);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function adminMenu(?callable $permissionChecker = null): array
    {
        $menu = [];

        foreach ($this->all() as $moduleKey => $module) {
            if (! (bool) ($module['enabled'] ?? false)) {
                continue;
            }

            $moduleMenu = $this->resolveModuleMenu((string) $moduleKey);

            if (is_array($moduleMenu)) {
                foreach ($moduleMenu as $menuItem) {
                    if (! is_array($menuItem)) {
                        continue;
                    }

                    $permission = $menuItem['permission'] ?? null;

                    if (! is_string($permission) || trim($permission) === '') {
                        $menu[] = $menuItem;

                        continue;
                    }

                    if ($permissionChecker !== null && $permissionChecker($permission, $menuItem) === true) {
                        $menu[] = $menuItem;
                    }
                }
            }
        }

        usort($menu, static function (array $left, array $right): int {
            $leftOrder = (int) ($left['order'] ?? PHP_INT_MAX);
            $rightOrder = (int) ($right['order'] ?? PHP_INT_MAX);

            if ($leftOrder !== $rightOrder) {
                return $leftOrder <=> $rightOrder;
            }

            $leftGroup = (string) ($left['group'] ?? 'zzz');
            $rightGroup = (string) ($right['group'] ?? 'zzz');

            if ($leftGroup !== $rightGroup) {
                return $leftGroup <=> $rightGroup;
            }

            return ((string) ($left['label'] ?? '')) <=> ((string) ($right['label'] ?? ''));
        });

        return $menu;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveModuleMenu(string $moduleKey): array
    {
        $resolved = $this->moduleMenus[$moduleKey] ?? null;

        return is_array($resolved) ? $resolved : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function health(): array
    {
        $knownModules = array_keys($this->modules);
        $runtimeStates = $this->moduleStateService?->states() ?? $this->runtimeStates;

        $unknownRuntimeModules = array_values(array_diff(array_keys($runtimeStates), $knownModules));

        return [
            'total_configured_modules' => count($knownModules),
            'total_runtime_modules' => count($runtimeStates),
            'known_modules_count' => count($knownModules),
            'runtime_states_count' => count($runtimeStates),
            'unknown_runtime_modules' => $unknownRuntimeModules,
            'healthy' => $unknownRuntimeModules === [],
        ];
    }
}
