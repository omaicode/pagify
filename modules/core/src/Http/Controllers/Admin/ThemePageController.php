<?php

namespace Pagify\Core\Http\Controllers\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Pagify\Core\Models\Admin;

class ThemePageController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Request $request): Response
    {
        $this->authorize('manageThemes', Admin::class);

        return Inertia::render('Admin/Themes/Index', [
            'apiRoutes' => [
                'index' => route('core.api.v1.admin.themes.index'),
                'store' => route('core.api.v1.admin.themes.store'),
                'updateBase' => route('core.api.v1.admin.themes.update', ['theme' => '__THEME__']),
                'activateBase' => route('core.api.v1.admin.themes.activate', ['theme' => '__THEME__']),
                'deleteBase' => route('core.api.v1.admin.themes.destroy', ['theme' => '__THEME__']),
            ],
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
