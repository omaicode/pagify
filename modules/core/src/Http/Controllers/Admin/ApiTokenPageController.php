<?php

namespace Modules\Core\Http\Controllers\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Admin;

class ApiTokenPageController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Request $request): Response
    {
        $this->authorize('manageTokens', Admin::class);

        return Inertia::render('Admin/Tokens/Index', [
            'apiRoutes' => [
                'index' => route('core.api.v1.admin.tokens.index'),
                'store' => route('core.api.v1.admin.tokens.store'),
            ],
        ]);
    }
}
