<?php

namespace Pagify\Core\Http\Controllers\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Pagify\Core\Models\Admin;
use Pagify\Core\Models\Site;

class ThemePageController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Request $request): Response
    {
        $this->authorize('manageThemes', Admin::class);

        return Inertia::render('Admin/Themes/Index', [
            'apiRoutes' => [
                'index' => route('core.api.v1.admin.themes.index'),
                'updateBase' => route('core.api.v1.admin.themes.update', ['theme' => '__THEME__']),
                'activateBase' => route('core.api.v1.admin.themes.activate', ['theme' => '__THEME__']),
                'deleteBase' => route('core.api.v1.admin.themes.destroy', ['theme' => '__THEME__']),
            ],
            'sites' => Site::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'domain'])
                ->map(static fn (Site $site): array => [
                    'id' => (int) $site->id,
                    'name' => (string) $site->name,
                    'slug' => (string) $site->slug,
                    'domain' => (string) ($site->domain ?? ''),
                ])
                ->values()
                ->all(),
            'breadcrumbs' => [
                [
                    'href' => route('core.admin.dashboard'),
                    'label_key' => 'dashboard',
                ],
                [
                    'href' => route('core.admin.settings.index'),
                    'label_key' => 'settings',
                ],
                [
                    'label_key' => 'settings_item_themes',
                ],
            ],
        ]);
    }
}
