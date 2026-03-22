<?php

use Illuminate\Support\Facades\Route;
use Pagify\Core\Http\Middleware\EnsureApiErrorEnvelope;
use Pagify\Core\Http\Middleware\EnsureModuleEnabled;
use Pagify\Core\Http\Middleware\HandleInertiaRequests;
use Pagify\Core\Http\Middleware\RecordAuditLog;
use Pagify\Core\Http\Middleware\ResolveSite;
use Pagify\Core\Http\Middleware\SetLocaleFromSite;
use Pagify\PageBuilder\Webstudio\Http\Controllers\AssetProxyController;
use Pagify\PageBuilder\Http\Controllers\Admin\EditorSpaController;
use Pagify\PageBuilder\Http\Controllers\Admin\PageController;
use Pagify\PageBuilder\Http\Controllers\Api\EditorTokenController;
use Pagify\PageBuilder\Http\Controllers\Api\EditorMediaController;
use Pagify\PageBuilder\Http\Controllers\Api\PageCrudController;
use Pagify\PageBuilder\Http\Controllers\Api\PageFolderController;
use Pagify\PageBuilder\Webstudio\Http\Controllers\Api\CompatController;
use Pagify\PageBuilder\Webstudio\Http\Controllers\Api\TrpcController;


Route::middleware(['web', ResolveSite::class, SetLocaleFromSite::class])->group(function (): void {
	Route::get('/cgi/image/{path?}', [AssetProxyController::class, 'image'])
		->where('path', '.*')
		->name('page-builder.webstudio.cgi.image');
	Route::get('/cgi/asset/{path?}', [AssetProxyController::class, 'asset'])
		->where('path', '.*')
		->name('page-builder.webstudio.cgi.asset');

	Route::prefix(config('app.admin_url_prefix').'/page-builder')
		->name('page-builder.admin.')
		->middleware([EnsureModuleEnabled::class . ':page-builder', HandleInertiaRequests::class, RecordAuditLog::class])
		->group(function (): void {
			Route::get('/editor-spa/{path?}', EditorSpaController::class)
				->where('path', '.*')
				->name('editor.spa');
		});
});

Route::middleware(['web', ResolveSite::class, SetLocaleFromSite::class])->group(function (): void {
	Route::prefix(config('app.admin_url_prefix').'/page-builder')
		->name('page-builder.admin.')
		->middleware([EnsureModuleEnabled::class . ':page-builder', 'auth:web', HandleInertiaRequests::class, RecordAuditLog::class])
		->group(function (): void {
			Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
			Route::get('/pages/{page}/preview', [PageController::class, 'preview'])->name('pages.preview');
		});
});

Route::prefix('api/v1/'.config('app.admin_url_prefix').'/page-builder')
	->middleware(['api', EnsureApiErrorEnvelope::class, ResolveSite::class, SetLocaleFromSite::class, EnsureModuleEnabled::class . ':page-builder', RecordAuditLog::class])
	->name('page-builder.api.v1.admin.')
	->group(function (): void {
		Route::post('/editor/access-token', EditorTokenController::class)->name('editor.access-token');
		Route::post('/dashboard-logout', [CompatController::class, 'dashboardLogout']);
		Route::get('/pages', [PageCrudController::class, 'index'])->name('pages.index');
		Route::post('/pages', [PageCrudController::class, 'store'])->name('pages.store');
		Route::put('/pages/{page}', [PageCrudController::class, 'update'])->name('pages.update');
		Route::post('/pages/{page}/publish', [PageCrudController::class, 'publish'])->name('pages.publish');
		Route::delete('/pages/{page}', [PageCrudController::class, 'destroy'])->name('pages.destroy');
		Route::get('/folders', [PageFolderController::class, 'index'])->name('folders.index');
		Route::post('/folders', [PageFolderController::class, 'store'])->name('folders.store');
		Route::put('/folders/{folderId}', [PageFolderController::class, 'update'])->name('folders.update');
		Route::delete('/folders/{folderId}', [PageFolderController::class, 'destroy'])->name('folders.destroy');
		Route::post('/folders/move', [PageFolderController::class, 'move'])->name('folders.move');

		Route::post('/editor/media/assets', [EditorMediaController::class, 'index'])->name('editor.media.assets');
		Route::post('/editor/media/assets/upload', [EditorMediaController::class, 'upload'])->name('editor.media.assets.upload');

        // Webstudio compatibility routes
		Route::get('/data/{projectId}', [CompatController::class, 'data'])
			->where('projectId', '[A-Za-z0-9_-]+');
		Route::get('/data/{projectId}/pages/{pageId}', [CompatController::class, 'dataPage'])
			->where('projectId', '[A-Za-z0-9_-]+')
			->where('pageId', '[0-9]+');
		Route::post('/patch', [CompatController::class, 'patch']);
		Route::post('/resources-loader', [CompatController::class, 'resourcesLoader']);
		Route::match(['GET', 'POST'], '/assets', [CompatController::class, 'assets']);
		Route::post('/assets/{name}', [CompatController::class, 'uploadAsset'])
			->where('name', '.+');
		Route::delete('/assets/{assetId}', [CompatController::class, 'deleteAsset'])
			->where('assetId', '[0-9]+');
		Route::match(['GET', 'POST'], '/trpc/{path?}', TrpcController::class)
			->where('path', '.*');
	});
