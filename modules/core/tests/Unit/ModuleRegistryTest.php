<?php

namespace Pagify\Core\Tests\Unit;

use Pagify\Core\Services\ModuleRegistry;
use PHPUnit\Framework\TestCase;

class ModuleRegistryTest extends TestCase
{
    public function test_admin_menu_filters_by_permission_checker(): void
    {
        $registry = new ModuleRegistry([
            'core' => [
                'enabled' => true,
            ],
        ], moduleMenus: [
            'core' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'core.admin.dashboard',
                    'permission' => null,
                ],
                [
                    'label' => 'API tokens',
                    'route' => 'core.admin.tokens.index',
                    'permission' => 'core.token.manage',
                ],
            ],
        ]);

        $menu = $registry->adminMenu(static fn (string $permission): bool => $permission === 'core.token.manage');

        $this->assertCount(2, $menu);
    }

    public function test_admin_menu_hides_permissioned_items_when_checker_denies(): void
    {
        $registry = new ModuleRegistry([
            'core' => [
                'enabled' => true,
            ],
        ], moduleMenus: [
            'core' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'core.admin.dashboard',
                    'permission' => null,
                ],
                [
                    'label' => 'API tokens',
                    'route' => 'core.admin.tokens.index',
                    'permission' => 'core.token.manage',
                ],
            ],
        ]);

        $menu = $registry->adminMenu(static fn (): bool => false);

        $this->assertCount(1, $menu);
        $this->assertSame('Dashboard', $menu[0]['label']);
    }

    public function test_admin_menu_merges_module_menus_and_sorts_by_order(): void
    {
        $registry = new ModuleRegistry([
            'core' => [
                'enabled' => true,
            ],
            'media' => [
                'enabled' => true,
            ],
        ], moduleMenus: [
            'core' => [
                [
                    'label' => 'Modules',
                    'route' => 'core.admin.modules.index',
                    'permission' => null,
                    'order' => 40,
                ],
            ],
            'media' => [
                [
                    'label' => 'Media',
                    'route' => 'media.admin.assets.index',
                    'permission' => null,
                    'order' => 10,
                ],
            ],
        ]);

        $menu = $registry->adminMenu();

        $this->assertCount(2, $menu);
        $this->assertSame('Media', $menu[0]['label']);
        $this->assertSame('Modules', $menu[1]['label']);
    }

    public function test_admin_menu_order_takes_priority_over_group_name(): void
    {
        $registry = new ModuleRegistry([
            'core' => [
                'enabled' => true,
            ],
            'media' => [
                'enabled' => true,
            ],
        ], moduleMenus: [
            'core' => [
                [
                    'label' => 'Modules',
                    'route' => 'core.admin.modules.index',
                    'permission' => null,
                    'group' => 'Core',
                    'order' => 40,
                ],
            ],
            'media' => [
                [
                    'label' => 'Media',
                    'route' => 'media.admin.assets.index',
                    'permission' => null,
                    'group' => 'Media',
                    'order' => 100,
                ],
            ],
        ]);

        $menu = $registry->adminMenu();

        $this->assertCount(2, $menu);
        $this->assertSame('Modules', $menu[0]['label']);
        $this->assertSame('Media', $menu[1]['label']);
    }
}
