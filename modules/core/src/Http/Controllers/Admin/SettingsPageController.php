<?php

namespace Pagify\Core\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Pagify\Core\Models\Admin;
use Pagify\Core\Services\ModuleRegistry;

class SettingsPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $admin = $request->user('web');
        /** @var ModuleRegistry $modules */
        $modules = app(ModuleRegistry::class);

        return Inertia::render('Admin/Settings/Index', [
            'groups' => array_values(array_filter([
                [
                    'key' => 'system',
                    'label' => 'System',
                    'label_key' => 'settings_group_system',
                    'items' => array_values(array_filter([
                        $this->makeItem(
                            label: 'Modules',
                            labelKey: 'settings_item_modules',
                            description: 'Enable or disable installed modules.',
                            descriptionKey: 'settings_item_modules_description',
                            href: route('core.admin.modules.index'),
                            allowed: $admin?->can('manageModules', Admin::class) === true,
                        ),
                        $this->makeItem(
                            label: 'Plugins',
                            labelKey: 'settings_item_plugins',
                            description: 'Install, uninstall, and control plugins from marketplace sources.',
                            descriptionKey: 'settings_item_plugins_description',
                            href: route('core.admin.plugins.index'),
                            allowed: $admin?->can('manageModules', Admin::class) === true,
                        ),
                        $this->makeItem(
                            label: 'Themes',
                            labelKey: 'settings_item_themes',
                            description: 'Create, update, activate, and remove frontend themes for each site.',
                            descriptionKey: 'settings_item_themes_description',
                            href: route('core.admin.themes.index'),
                            allowed: $admin?->can('manageThemes', Admin::class) === true,
                        ),
                        $this->makeItem(
                            label: 'Updater',
                            labelKey: 'settings_item_updater',
                            description: 'Queue updates, monitor progress, and rollback failed executions.',
                            descriptionKey: 'settings_item_updater_description',
                            href: Route::has('updater.admin.index') ? route('updater.admin.index') : '#',
                            allowed: $modules->enabled('updater')
                                && Route::has('updater.admin.index')
                                && ($admin?->can('manageUpdater', Admin::class) === true),
                        ),
                    ])),
                ],
                [
                    'key' => 'security',
                    'label' => 'Security',
                    'label_key' => 'settings_group_security',
                    'items' => array_values(array_filter([
                        $this->makeItem(
                            label: 'Permissions',
                            labelKey: 'settings_item_permissions',
                            description: 'Manage access permissions available for administrator groups.',
                            descriptionKey: 'settings_item_permissions_description',
                            href: route('core.admin.permissions.index'),
                            allowed: $admin?->can('managePermissions', Admin::class) === true,
                        ),
                        $this->makeItem(
                            label: 'Administrator groups',
                            labelKey: 'settings_item_admin_groups',
                            description: 'Create and maintain administrator groups and their permission matrix.',
                            descriptionKey: 'settings_item_admin_groups_description',
                            href: route('core.admin.admin-groups.index'),
                            allowed: $admin?->can('manageAdminGroups', Admin::class) === true,
                        ),
                        $this->makeItem(
                            label: 'Administrators',
                            labelKey: 'settings_item_admins',
                            description: 'Manage administrator accounts and assign group memberships.',
                            descriptionKey: 'settings_item_admins_description',
                            href: route('core.admin.admins.index'),
                            allowed: $admin?->can('manageAdmins', Admin::class) === true,
                        ),
                        $this->makeItem(
                            label: 'API tokens',
                            labelKey: 'settings_item_api_tokens',
                            description: 'Create and revoke admin API tokens.',
                            descriptionKey: 'settings_item_api_tokens_description',
                            href: route('core.admin.tokens.index'),
                            allowed: $admin?->can('manageTokens', Admin::class) === true,
                        ),
                    ])),
                ],
                [
                    'key' => 'audit',
                    'label' => 'Monitoring',
                    'label_key' => 'settings_group_monitoring',
                    'items' => array_values(array_filter([
                        $this->makeItem(
                            label: 'Audit logs',
                            labelKey: 'settings_item_audit_logs',
                            description: 'Review administrative activities and changes.',
                            descriptionKey: 'settings_item_audit_logs_description',
                            href: route('core.admin.audit.index'),
                            allowed: $admin?->can('core.audit.view') || $admin?->can('core.audit.viewAny'),
                        ),
                    ])),
                ],
            ], static fn (array $group): bool => ($group['items'] ?? []) !== [])),
            'breadcrumbs' => [
                [
                    'href' => route('core.admin.dashboard'),
                    'label_key' => 'dashboard',
                ],
                [
                    'label_key' => 'settings',
                ],
            ],
        ]);
    }

    /**
     * @return array<string, string>|null
     */
    private function makeItem(string $label, string $labelKey, string $description, string $descriptionKey, string $href, bool $allowed): ?array
    {
        if (! $allowed) {
            return null;
        }

        return [
            'label' => $label,
            'label_key' => $labelKey,
            'description' => $description,
            'description_key' => $descriptionKey,
            'href' => $href,
        ];
    }
}
