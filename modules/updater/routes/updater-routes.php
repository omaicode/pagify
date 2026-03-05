<?php

use Illuminate\Support\Facades\Route;
use Pagify\Core\Http\Middleware\EnsureApiErrorEnvelope;
use Pagify\Core\Http\Middleware\HandleInertiaRequests;
use Pagify\Core\Http\Middleware\RecordAuditLog;
use Pagify\Core\Http\Middleware\ResolveSite;
use Pagify\Core\Http\Middleware\SetLocaleFromSite;
use Pagify\Updater\Http\Controllers\Admin\UpdaterPageController;
use Pagify\Updater\Http\Controllers\Api\AdminUpdaterExecutionController;

Route::middleware(['web', ResolveSite::class, SetLocaleFromSite::class])
	->group(function (): void {
		Route::prefix('admin')
			->name('updater.admin.')
			->middleware(['auth:web', HandleInertiaRequests::class, RecordAuditLog::class])
			->group(function (): void {
				Route::get('/updater', UpdaterPageController::class)->name('index');
			});
	});

Route::prefix('api/v1/admin/updater')
	->middleware(['web', EnsureApiErrorEnvelope::class, ResolveSite::class, SetLocaleFromSite::class, 'auth:web', 'throttle:120,1', RecordAuditLog::class])
	->name('updater.api.v1.admin.')
	->group(function (): void {
		Route::get('/executions', [AdminUpdaterExecutionController::class, 'index'])->name('executions.index');
		Route::get('/executions/{execution}', [AdminUpdaterExecutionController::class, 'show'])->name('executions.show');
		Route::post('/executions/dry-run', [AdminUpdaterExecutionController::class, 'dryRun'])->name('executions.dry-run');
		Route::post('/executions/module/{module}', [AdminUpdaterExecutionController::class, 'updateModule'])->name('executions.module');
		Route::post('/executions/all', [AdminUpdaterExecutionController::class, 'updateAll'])->name('executions.all');
		Route::post('/executions/{execution}/rollback', [AdminUpdaterExecutionController::class, 'rollback'])->name('executions.rollback');
	});
