<?php

namespace Modules\Content\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ContentDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('content::index', [
            'title' => 'Content module',
            'description' => 'Content module bootstrap is ready. Continue implementation from roadmap phase C1.',
        ]);
    }
}
