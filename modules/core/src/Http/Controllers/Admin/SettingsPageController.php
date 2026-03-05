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
                            label: 'Content',
                            labelKey: 'settings_item_content',
                            description: 'Manage content types, schema builder, and entries.',
                            descriptionKey: 'settings_item_content_description',
                            href: Route::has('content.admin.dashboard') ? route('content.admin.dashboard') : '#',
                            allowed: $modules->enabled('content')
                                && Route::has('content.admin.dashboard')
                                && ($admin?->can('content.type.viewAny') === true),
                        ),
                        $this->makeItem(
                            label: 'Modules',
                            labelKey: 'settings_item_modules',
                            description: 'Enable or disable installed modules.',
                            descriptionKey: 'settings_item_modules_description',
                            href: route('core.admin.modules.index'),
                            allowed: $admin?->can('manageModules', Admin::class) === true,
                        ),
                    ])),
                ],
                [
                    'key' => 'security',
                    'label' => 'Security',
                    'label_key' => 'settings_group_security',
                    'items' => array_values(array_filter([
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
