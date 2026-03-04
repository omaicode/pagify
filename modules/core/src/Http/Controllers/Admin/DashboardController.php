<?php

namespace Modules\Core\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Admin;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $admin = $request->user('web');

        return Inertia::render('Admin/Dashboard', [
            'message' => __('core::messages.dashboard_active'),
            'admin' => $admin?->only(['id', 'name', 'username', 'email']),
            'canManageTokens' => $admin?->can('manageTokens', Admin::class) === true,
        ]);
    }
}
