<?php

namespace Pagify\Core\Http\Controllers\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Pagify\Core\Models\Admin;

class ModulePageController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Request $request): Response
    {
        $this->authorize('manageModules', Admin::class);

        return Inertia::render('Admin/Modules/Index', [
            'apiRoutes' => [
                'index' => route('core.api.v1.admin.modules.index'),
                'health' => route('core.api.v1.admin.modules.health'),
                'updateBase' => route('core.api.v1.admin.modules.update', ['module' => '__MODULE__']),
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
                    'label_key' => 'settings_item_modules',
                ],
            ],
        ]);
    }
}
