<?php

namespace Pagify\Core\Http\Controllers\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Pagify\Core\Models\Admin;

class PluginPageController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Request $request): Response
    {
        $this->authorize('manageModules', Admin::class);

        return Inertia::render('Admin/Plugins/Index', [
            'apiRoutes' => [
                'pluginsIndex' => route('core.api.v1.admin.plugins.index'),
                'pluginUpdateBase' => route('core.api.v1.admin.plugins.update', ['plugin' => '__PLUGIN__']),
                'pluginInstallComposer' => route('core.api.v1.admin.plugins.install.composer'),
                'pluginInstallZip' => route('core.api.v1.admin.plugins.install.zip'),
                'pluginUninstallBase' => route('core.api.v1.admin.plugins.destroy', ['plugin' => '__PLUGIN__']),
                'pluginExtensions' => route('core.api.v1.admin.plugins.extensions'),
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
                    'label_key' => 'settings_item_plugins',
                ],
            ],
        ]);
    }
}
