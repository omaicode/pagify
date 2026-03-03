<?php

namespace Modules\Core\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'message' => 'Core admin shell is active.',
            'admin' => $request->user('web')?->only(['id', 'name', 'username', 'email']),
        ]);
    }
}
