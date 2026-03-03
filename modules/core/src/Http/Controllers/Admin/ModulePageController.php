<?php

namespace Modules\Core\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Admin;

class ModulePageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('manageModules', Admin::class);

        return Inertia::render('Admin/Modules/Index', [
            'apiRoutes' => [
                'index' => route('core.api.v1.admin.modules.index'),
                'health' => route('core.api.v1.admin.modules.health'),
                'updateBase' => route('core.api.v1.admin.modules.update', ['module' => '__MODULE__']),
            ],
        ]);
    }
}
