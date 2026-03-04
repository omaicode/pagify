<?php

namespace Modules\Media\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class MediaLibraryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Media/Library/Index', [
            'apiRoutes' => [
                'assetsIndex' => route('media.api.v1.admin.assets.index'),
                'assetsStore' => route('media.api.v1.admin.assets.store'),
                'assetsDestroyBase' => route('media.api.v1.admin.assets.destroy', ['asset' => '__ASSET__']),
                'assetsPreviewBase' => route('media.api.v1.admin.assets.preview', ['asset' => '__ASSET__']),
                'assetsDownloadBase' => route('media.api.v1.admin.assets.download', ['asset' => '__ASSET__']),
                'uploadSessionsStore' => route('media.api.v1.admin.upload-sessions.store'),
                'uploadChunkBase' => route('media.api.v1.admin.upload-sessions.chunk', ['session' => '__SESSION__']),
                'uploadCompleteBase' => route('media.api.v1.admin.upload-sessions.complete', ['session' => '__SESSION__']),
                'foldersIndex' => route('media.api.v1.admin.folders.index'),
                'foldersStore' => route('media.api.v1.admin.folders.store'),
            ],
            'defaultViewMode' => 'grid',
        ]);
    }
}
