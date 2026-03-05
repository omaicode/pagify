<?php

use Illuminate\Support\Facades\Route;
use Pagify\Content\Http\Controllers\Admin\ContentDashboardController;
use Pagify\Content\Http\Controllers\Admin\ContentEntryController;
use Pagify\Content\Http\Controllers\Admin\ContentEntryRevisionController;
use Pagify\Content\Http\Controllers\Admin\ContentTypeBuilderController;
use Pagify\Content\Http\Controllers\Admin\ContentTypeController;
use Pagify\Content\Http\Controllers\Api\AdminRelationPickerController;
use Pagify\Content\Http\Controllers\Api\ContentApiController;
use Pagify\Content\Http\Controllers\Api\ContentHealthController;
use Pagify\Core\Http\Middleware\EnsureApiErrorEnvelope;
use Pagify\Core\Http\Middleware\EnsureModuleEnabled;
use Pagify\Core\Http\Middleware\HandleInertiaRequests;
use Pagify\Core\Http\Middleware\RecordAuditLog;
use Pagify\Core\Http\Middleware\ResolveSite;
use Pagify\Core\Http\Middleware\SetLocaleFromSite;

Route::middleware(['web', ResolveSite::class, SetLocaleFromSite::class])->group(function (): void {
	Route::prefix('admin/content')
		->name('content.admin.')
		->middleware([EnsureModuleEnabled::class . ':content', 'auth:web', HandleInertiaRequests::class, RecordAuditLog::class])
		->group(function (): void {
			Route::get('/', ContentDashboardController::class)->name('dashboard');
			Route::get('/types', [ContentTypeController::class, 'index'])->name('types.index');
			Route::get('/types/create', [ContentTypeController::class, 'create'])->name('types.create');
			Route::post('/types', [ContentTypeController::class, 'store'])->name('types.store');
			Route::get('/types/{contentType}/builder', [ContentTypeBuilderController::class, 'edit'])->name('types.builder.edit');
			Route::put('/types/{contentType}/builder', [ContentTypeBuilderController::class, 'update'])->name('types.builder.update');
			Route::get('/types/{contentType}/builder/status', [ContentTypeBuilderController::class, 'status'])->name('types.builder.status');
			Route::post('/types/{contentType}/builder/plans/{plan}/retry', [ContentTypeBuilderController::class, 'retry'])->name('types.builder.retry');
			Route::get('/types/{contentType}/edit', [ContentTypeController::class, 'edit'])->name('types.edit');
			Route::put('/types/{contentType}', [ContentTypeController::class, 'update'])->name('types.update');
			Route::delete('/types/{contentType}', [ContentTypeController::class, 'destroy'])->name('types.destroy');

			Route::get('/{contentTypeSlug}/entries', [ContentEntryController::class, 'index'])->name('entries.index');
			Route::get('/{contentTypeSlug}/entries/create', [ContentEntryController::class, 'create'])->name('entries.create');
			Route::post('/{contentTypeSlug}/entries', [ContentEntryController::class, 'store'])->name('entries.store');
			Route::get('/{contentTypeSlug}/entries/{entryId}/edit', [ContentEntryController::class, 'edit'])->name('entries.edit');
			Route::put('/{contentTypeSlug}/entries/{entryId}', [ContentEntryController::class, 'update'])->name('entries.update');
			Route::post('/{contentTypeSlug}/entries/{entryId}/publish', [ContentEntryController::class, 'publish'])->name('entries.publish');
			Route::post('/{contentTypeSlug}/entries/{entryId}/unpublish', [ContentEntryController::class, 'unpublish'])->name('entries.unpublish');
			Route::post('/{contentTypeSlug}/entries/{entryId}/schedule', [ContentEntryController::class, 'schedule'])->name('entries.schedule');
			Route::delete('/{contentTypeSlug}/entries/{entryId}', [ContentEntryController::class, 'destroy'])->name('entries.destroy');
			Route::get('/{contentTypeSlug}/entries/{entryId}/revisions', [ContentEntryRevisionController::class, 'index'])->name('entries.revisions.index');
			Route::post('/{contentTypeSlug}/entries/{entryId}/revisions/{revisionId}/rollback', [ContentEntryRevisionController::class, 'rollback'])->name('entries.revisions.rollback');
		});
});

Route::prefix('api/v1/content')
	->middleware(['api', EnsureApiErrorEnvelope::class, ResolveSite::class, SetLocaleFromSite::class, EnsureModuleEnabled::class . ':content', 'auth:sanctum', 'throttle:60,1'])
	->name('content.api.v1.')
	->group(function (): void {
		Route::get('/health', [ContentHealthController::class, 'public'])->name('health');
		Route::get('/{contentTypeSlug}', [ContentApiController::class, 'index'])->name('entries.index');
		Route::get('/{contentTypeSlug}/{entrySlug}', [ContentApiController::class, 'show'])->name('entries.show');
	});

Route::prefix('api/v1/admin/content')
	->middleware(['web', EnsureApiErrorEnvelope::class, ResolveSite::class, SetLocaleFromSite::class, EnsureModuleEnabled::class . ':content', 'auth:web', 'throttle:60,1', RecordAuditLog::class])
	->name('content.api.v1.admin.')
	->group(function (): void {
		Route::get('/health', [ContentHealthController::class, 'admin'])->name('health');
		Route::get('/{contentTypeSlug}/relations/picker', AdminRelationPickerController::class)->name('relations.picker');
	});
