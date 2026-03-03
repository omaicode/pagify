<?php

namespace Modules\Core\Services;

class ModuleRegistry
{
    /**
     * @param array<string, array<string, mixed>> $modules
     */
    public function __construct(private readonly array $modules)
    {
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->modules;
    }

    public function has(string $module): bool
    {
        return isset($this->modules[$module]);
    }

    public function enabled(string $module): bool
    {
        if (! $this->has($module)) {
            return false;
        }

        return (bool) ($this->modules[$module]['enabled'] ?? false);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function adminMenu(?callable $permissionChecker = null): array
    {
        $menu = [];

        foreach ($this->modules as $module) {
            if (! (bool) ($module['enabled'] ?? false)) {
                continue;
            }

            $moduleMenu = $module['menu'] ?? [];

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

        return $menu;
    }
}
