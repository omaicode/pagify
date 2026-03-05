<?php

namespace Pagify\Core\Http\Controllers\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Pagify\Core\Models\Admin;

class AdminManagerPageController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Request $request): Response
    {
        $this->authorize('manageAdmins', Admin::class);

        return Inertia::render('Admin/Admins/Index', [
            'apiRoutes' => [
                'index' => route('core.api.v1.admin.admins.index'),
                'groups' => route('core.api.v1.admin.admin-groups.index'),
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
                    'label_key' => 'settings_item_admins',
                ],
            ],
        ]);
    }
}
