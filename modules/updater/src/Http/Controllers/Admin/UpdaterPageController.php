<?php

namespace Pagify\Updater\Http\Controllers\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Pagify\Core\Models\Admin;

class UpdaterPageController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Request $request): Response
    {
        $this->authorize('manageUpdater', Admin::class);

        return Inertia::render('Admin/Updater/Index', [
            'apiRoutes' => [
                'index' => route('updater.api.v1.admin.executions.index'),
                'showBase' => route('updater.api.v1.admin.executions.show', ['execution' => '__EXECUTION__']),
                'dryRun' => route('updater.api.v1.admin.executions.dry-run'),
                'updateModuleBase' => route('updater.api.v1.admin.executions.module', ['module' => '__MODULE__']),
                'updateAll' => route('updater.api.v1.admin.executions.all'),
                'rollbackBase' => route('updater.api.v1.admin.executions.rollback', ['execution' => '__EXECUTION__']),
            ],
        ]);
    }
}
