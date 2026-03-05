<?php

use Illuminate\Support\Facades\Route;
use Pagify\Core\Http\Middleware\EnsureApiErrorEnvelope;
use Pagify\Core\Http\Middleware\EnsureModuleEnabled;
use Pagify\Core\Http\Middleware\HandleInertiaRequests;
use Pagify\Core\Http\Middleware\RecordAuditLog;
use Pagify\Core\Http\Middleware\ResolveSite;
use Pagify\Core\Http\Middleware\SetLocaleFromSite;
use Pagify\PageBuilder\Http\Controllers\Admin\PageController;
use Pagify\PageBuilder\Http\Controllers\Admin\PageLibraryController;
use Pagify\PageBuilder\Http\Controllers\Admin\PageRevisionController;
use Pagify\PageBuilder\Http\Controllers\Api\AdminPageBuilderRegistryController;
use Pagify\PageBuilder\Http\Controllers\PublicPageController;

Route::middleware(['web', ResolveSite::class, SetLocaleFromSite::class])->group(function (): void {
	Route::prefix(config('app.admin_url_prefix').'/page-builder')
		->name('page-builder.admin.')
		->middleware([EnsureModuleEnabled::class . ':page-builder', 'auth:web', HandleInertiaRequests::class, RecordAuditLog::class])
		->group(function (): void {
			Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
			Route::get('/pages/create', [PageController::class, 'create'])->name('pages.create');
			Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
			Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
			Route::put('/pages/{page}', [PageController::class, 'update'])->name('pages.update');
			Route::post('/pages/{page}/publish', [PageController::class, 'publish'])->name('pages.publish');
			Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
			Route::post('/library/sections', [PageLibraryController::class, 'storeSection'])->name('library.sections.store');
			Route::post('/library/templates', [PageLibraryController::class, 'storePageTemplate'])->name('library.templates.store');

			Route::get('/pages/{page}/revisions', [PageRevisionController::class, 'index'])->name('pages.revisions.index');
			Route::post('/pages/{page}/revisions/{revision}/rollback', [PageRevisionController::class, 'rollback'])->name('pages.revisions.rollback');
		});
});

Route::prefix('api/v1/'.config('app.admin_url_prefix').'/page-builder')
	->middleware(['web', EnsureApiErrorEnvelope::class, ResolveSite::class, SetLocaleFromSite::class, EnsureModuleEnabled::class . ':page-builder', 'auth:web', 'throttle:60,1', RecordAuditLog::class])
	->name('page-builder.api.v1.admin.')
	->group(function (): void {
		Route::get('/registry', [AdminPageBuilderRegistryController::class, 'registry'])->name('registry');
		Route::get('/templates', [AdminPageBuilderRegistryController::class, 'templates'])->name('templates');
		Route::get('/sections', [AdminPageBuilderRegistryController::class, 'sections'])->name('sections');
	});

Route::middleware(['web', ResolveSite::class, SetLocaleFromSite::class, EnsureModuleEnabled::class . ':page-builder'])
	->get('/pages/{slug}', PublicPageController::class)
	->name('page-builder.pages.show');
