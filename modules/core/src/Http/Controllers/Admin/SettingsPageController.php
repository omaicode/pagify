<?php

namespace Modules\Core\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Admin;
use Modules\Core\Services\ModuleRegistry;

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
                    'items' => array_values(array_filter([
                        $this->makeItem(
                            label: 'Content',
                            description: 'Manage content types, schema builder, and entries.',
                            href: Route::has('content.admin.dashboard') ? route('content.admin.dashboard') : '#',
                            allowed: $modules->enabled('content')
                                && Route::has('content.admin.dashboard')
                                && ($admin?->can('content.type.viewAny') === true),
                        ),
                        $this->makeItem(
                            label: 'Modules',
                            description: 'Enable or disable installed modules.',
                            href: route('core.admin.modules.index'),
                            allowed: $admin?->can('manageModules', Admin::class) === true,
                        ),
                    ])),
                ],
                [
                    'key' => 'security',
                    'label' => 'Security',
                    'items' => array_values(array_filter([
                        $this->makeItem(
                            label: 'API tokens',
                            description: 'Create and revoke admin API tokens.',
                            href: route('core.admin.tokens.index'),
                            allowed: $admin?->can('manageTokens', Admin::class) === true,
                        ),
                    ])),
                ],
                [
                    'key' => 'audit',
                    'label' => 'Monitoring',
                    'items' => array_values(array_filter([
                        $this->makeItem(
                            label: 'Audit logs',
                            description: 'Review administrative activities and changes.',
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
    private function makeItem(string $label, string $description, string $href, bool $allowed): ?array
    {
        if (! $allowed) {
            return null;
        }

        return [
            'label' => $label,
            'description' => $description,
            'href' => $href,
        ];
    }
}
