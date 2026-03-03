<?php

namespace Tests\Unit;

use Modules\Core\Services\ModuleRegistry;
use PHPUnit\Framework\TestCase;

class ModuleRegistryTest extends TestCase
{
    public function test_admin_menu_filters_by_permission_checker(): void
    {
        $registry = new ModuleRegistry([
            'core' => [
                'enabled' => true,
                'menu' => [
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
                'menu' => [
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
            ],
        ]);

        $menu = $registry->adminMenu(static fn (): bool => false);

        $this->assertCount(1, $menu);
        $this->assertSame('Dashboard', $menu[0]['label']);
    }
}
