<?php

use Illuminate\Support\Facades\Route;
use Pagify\Core\Http\Middleware\EnsureApiErrorEnvelope;
use Pagify\Core\Http\Middleware\EnsureModuleEnabled;
use Pagify\Core\Http\Middleware\HandleInertiaRequests;
use Pagify\Core\Http\Middleware\RecordAuditLog;
use Pagify\Core\Http\Middleware\ResolveSite;
use Pagify\Core\Http\Middleware\SetLocaleFromSite;
use Pagify\PageBuilder\Http\Controllers\Admin\PageController;
use Pagify\PageBuilder\Http\Controllers\Api\AdminPageBuilderEditorTokenController;
use Pagify\PageBuilder\Http\Controllers\Api\AdminPageBuilderEditorContractController;
use Pagify\PageBuilder\Http\Controllers\Api\AdminPageBuilderEditorTokenVerifyController;
use Pagify\PageBuilder\Http\Controllers\Api\AdminPageBuilderRegistryController;

Route::middleware(['web', ResolveSite::class, SetLocaleFromSite::class])->group(function (): void {
	Route::prefix(config('app.admin_url_prefix').'/page-builder')
		->name('page-builder.admin.')
		->middleware([EnsureModuleEnabled::class . ':page-builder', 'auth:web', HandleInertiaRequests::class, RecordAuditLog::class])
		->group(function (): void {
			Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
			Route::get('/pages/create', [PageController::class, 'create'])->name('pages.create');
			Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
			Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
			Route::get('/pages/{page}/preview', [PageController::class, 'preview'])->name('pages.preview');
			Route::put('/pages/{page}', [PageController::class, 'update'])->name('pages.update');
			Route::post('/pages/{page}/publish', [PageController::class, 'publish'])->name('pages.publish');
			Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
		});
});

Route::prefix('api/v1/'.config('app.admin_url_prefix').'/page-builder')
	->middleware(['web', EnsureApiErrorEnvelope::class, ResolveSite::class, SetLocaleFromSite::class, EnsureModuleEnabled::class . ':page-builder', 'auth:web', 'throttle:60,1', RecordAuditLog::class])
	->name('page-builder.api.v1.admin.')
	->group(function (): void {
		Route::get('/registry', [AdminPageBuilderRegistryController::class, 'registry'])->name('registry');
		Route::post('/editor/access-token', AdminPageBuilderEditorTokenController::class)->name('editor.access-token');
	});

Route::prefix('api/v1/'.config('app.admin_url_prefix').'/page-builder')
	->middleware(['api', EnsureApiErrorEnvelope::class, ResolveSite::class, SetLocaleFromSite::class, EnsureModuleEnabled::class . ':page-builder', 'throttle:60,1'])
	->name('page-builder.api.v1.admin.')
	->group(function (): void {
		Route::get('/editor/contract', AdminPageBuilderEditorContractController::class)->name('editor.contract');
		Route::post('/editor/verify-token', AdminPageBuilderEditorTokenVerifyController::class)->name('editor.verify-token');
	});

