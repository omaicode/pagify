<?php

namespace Modules\Content\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Content\Models\ContentEntry;
use Modules\Content\Models\ContentType;

class ContentDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Content/Dashboard', [
            'title' => 'Content module',
            'description' => 'Manage content types, entries, and publishing workflows from shared admin shell.',
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
