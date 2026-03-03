<?php

namespace Modules\Core\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ApiTokenPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user('web')?->can('core.token.manage'), 403);

        return Inertia::render('Admin/Tokens/Index', [
            'apiRoutes' => [
                'index' => route('core.api.v1.admin.tokens.index'),
                'store' => route('core.api.v1.admin.tokens.store'),
            ],
        ]);
    }
}
