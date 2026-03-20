<?php

use Illuminate\Support\Facades\Route;
use Pagify\Core\Http\Middleware\EnsureApiErrorEnvelope;
use Pagify\Core\Http\Middleware\EnsureModuleEnabled;
use Pagify\Core\Http\Middleware\HandleInertiaRequests;
use Pagify\Core\Http\Middleware\RecordAuditLog;
use Pagify\Core\Http\Middleware\ResolveSite;
use Pagify\Core\Http\Middleware\SetLocaleFromSite;
use Pagify\PageBuilder\Http\Controllers\WebstudioAssetProxyController;
use Pagify\PageBuilder\Http\Controllers\Admin\EditorSpaController;
use Pagify\PageBuilder\Http\Controllers\Admin\PageController;
use Pagify\PageBuilder\Http\Controllers\Api\EditorTokenController;
use Pagify\PageBuilder\Http\Controllers\Api\EditorContractController;
use Pagify\PageBuilder\Http\Controllers\Api\EditorBuilderDataController;
use Pagify\PageBuilder\Http\Controllers\Api\EditorMediaController;
use Pagify\PageBuilder\Http\Controllers\Api\EditorTokenVerifyController;
use Pagify\PageBuilder\Http\Controllers\Api\PageCrudController;
use Pagify\PageBuilder\Http\Controllers\Api\RegistryController;
use Pagify\PageBuilder\Http\Controllers\Api\WebstudioCompatController;
use Pagify\PageBuilder\Http\Controllers\Api\WebstudioTrpcController;


Route::middleware(['web', ResolveSite::class, SetLocaleFromSite::class])->group(function (): void {
	Route::get('/cgi/image/{path?}', [WebstudioAssetProxyController::class, 'image'])
		->where('path', '.*')
		->name('page-builder.webstudio.cgi.image');
	Route::get('/cgi/asset/{path?}', [WebstudioAssetProxyController::class, 'asset'])
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
		Route::get('/registry', [RegistryController::class, 'registry'])->name('registry');
		Route::post('/editor/access-token', EditorTokenController::class)->name('editor.access-token');
		Route::post('/dashboard-logout', [WebstudioCompatController::class, 'dashboardLogout']);
		Route::get('/pages', [PageCrudController::class, 'index'])->name('pages.index');
		Route::post('/pages', [PageCrudController::class, 'store'])->name('pages.store');
		Route::put('/pages/{page}', [PageCrudController::class, 'update'])->name('pages.update');
		Route::post('/pages/{page}/publish', [PageCrudController::class, 'publish'])->name('pages.publish');
		Route::delete('/pages/{page}', [PageCrudController::class, 'destroy'])->name('pages.destroy');

		Route::get('/editor/contract', EditorContractController::class)->name('editor.contract');
		Route::post('/editor/verify-token', EditorTokenVerifyController::class)->name('editor.verify-token');
		Route::post('/editor/builder-data', EditorBuilderDataController::class)->name('editor.builder-data');
		Route::post('/editor/media/assets', [EditorMediaController::class, 'index'])->name('editor.media.assets');
		Route::post('/editor/media/assets/upload', [EditorMediaController::class, 'upload'])->name('editor.media.assets.upload');

        // Webstudio compatibility routes
		Route::get('/data/{projectId}', [WebstudioCompatController::class, 'data'])
			->where('projectId', '[A-Za-z0-9_-]+');
		Route::get('/data/{projectId}/pages/{pageId}', [WebstudioCompatController::class, 'dataPage'])
			->where('projectId', '[A-Za-z0-9_-]+')
			->where('pageId', '[0-9]+');
		Route::post('/patch', [WebstudioCompatController::class, 'patch']);
		Route::post('/resources-loader', [WebstudioCompatController::class, 'resourcesLoader']);
		Route::match(['GET', 'POST'], '/assets', [WebstudioCompatController::class, 'assets']);
		Route::post('/assets/{name}', [WebstudioCompatController::class, 'uploadAsset'])
			->where('name', '.+');
		Route::delete('/assets/{assetId}', [WebstudioCompatController::class, 'deleteAsset'])
			->where('assetId', '[0-9]+');
		Route::match(['GET', 'POST'], '/trpc/{path?}', WebstudioTrpcController::class)
			->where('path', '.*');
	});
