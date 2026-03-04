<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\EnsureApiErrorEnvelope;
use Modules\Core\Http\Middleware\EnsureModuleEnabled;
use Modules\Core\Http\Middleware\HandleInertiaRequests;
use Modules\Core\Http\Middleware\RecordAuditLog;
use Modules\Core\Http\Middleware\ResolveSite;
use Modules\Core\Http\Middleware\SetLocaleFromSite;
use Modules\Media\Http\Controllers\Admin\MediaLibraryController;
use Modules\Media\Http\Controllers\Api\AdminMediaAssetController;
use Modules\Media\Http\Controllers\Api\AdminMediaFolderController;
use Modules\Media\Http\Controllers\Api\AdminMediaUploadController;

Route::middleware(['web', ResolveSite::class, SetLocaleFromSite::class])->group(function (): void {
	Route::prefix('admin/media')
		->name('media.admin.')
		->middleware([EnsureModuleEnabled::class . ':media', 'auth:web', HandleInertiaRequests::class, RecordAuditLog::class])
		->group(function (): void {
			Route::get('/', [MediaLibraryController::class, 'index'])->name('library.index');
		});
});

Route::prefix('api/v1/admin/media')
	->middleware(['web', EnsureApiErrorEnvelope::class, ResolveSite::class, SetLocaleFromSite::class, EnsureModuleEnabled::class . ':media', 'auth:web', 'throttle:60,1', RecordAuditLog::class])
	->name('media.api.v1.admin.')
	->group(function (): void {
		Route::get('/assets', [AdminMediaAssetController::class, 'index'])->name('assets.index');
		Route::get('/assets/{asset}/preview', [AdminMediaAssetController::class, 'preview'])->name('assets.preview');
		Route::get('/assets/{asset}/download', [AdminMediaAssetController::class, 'download'])->name('assets.download');
		Route::post('/assets', [AdminMediaAssetController::class, 'store'])->name('assets.store');
		Route::patch('/assets/{asset}', [AdminMediaAssetController::class, 'update'])->name('assets.update');
		Route::delete('/assets/{asset}', [AdminMediaAssetController::class, 'destroy'])->name('assets.destroy');

		Route::get('/folders', [AdminMediaFolderController::class, 'index'])->name('folders.index');
		Route::post('/folders', [AdminMediaFolderController::class, 'store'])->name('folders.store');

		Route::post('/upload-sessions', [AdminMediaUploadController::class, 'store'])->name('upload-sessions.store');
		Route::post('/upload-sessions/{session}/chunk', [AdminMediaUploadController::class, 'uploadChunk'])->name('upload-sessions.chunk');
		Route::post('/upload-sessions/{session}/complete', [AdminMediaUploadController::class, 'complete'])->name('upload-sessions.complete');
	});
