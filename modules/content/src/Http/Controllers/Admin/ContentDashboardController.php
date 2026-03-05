<?php

namespace Pagify\Content\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Pagify\Content\Models\ContentEntry;
use Pagify\Content\Models\ContentType;

class ContentDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Content/Dashboard', [
            'title' => __('content::messages.dashboard.title'),
            'description' => __('content::messages.dashboard.description'),
            'stats' => [
                'types' => ContentType::query()->count(),
                'entries' => ContentEntry::query()->count(),
                'publishedEntries' => ContentEntry::query()->where('status', 'published')->count(),
            ],
            'routes' => [
                'typesIndex' => route('content.admin.types.index'),
                'apiEntries' => route('content.api.v1.entries.index', ['contentTypeSlug' => 'article']),
            ],
        ]);
    }
}
