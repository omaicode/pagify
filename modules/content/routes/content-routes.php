<?php

use Illuminate\Support\Facades\Route;
use Modules\Content\Http\Controllers\Admin\ContentDashboardController;
use Modules\Content\Http\Controllers\Api\ContentHealthController;
use Modules\Core\Http\Middleware\EnsureApiErrorEnvelope;
use Modules\Core\Http\Middleware\HandleInertiaRequests;
use Modules\Core\Http\Middleware\RecordAuditLog;
use Modules\Core\Http\Middleware\ResolveSite;
use Modules\Core\Http\Middleware\SetLocaleFromSite;

Route::middleware(['web', ResolveSite::class, SetLocaleFromSite::class])->group(function (): void {
	Route::prefix('admin/content')
		->name('content.admin.')
		->middleware(['auth:web', HandleInertiaRequests::class, RecordAuditLog::class])
		->group(function (): void {
			Route::get('/', ContentDashboardController::class)->name('dashboard');
		});
});

Route::prefix('api/v1/content')
	->middleware(['api', EnsureApiErrorEnvelope::class, ResolveSite::class, SetLocaleFromSite::class, 'auth:sanctum', 'throttle:60,1'])
	->name('content.api.v1.')
	->group(function (): void {
		Route::get('/health', [ContentHealthController::class, 'public'])->name('health');
	});

Route::prefix('api/v1/admin/content')
	->middleware(['web', EnsureApiErrorEnvelope::class, ResolveSite::class, SetLocaleFromSite::class, 'auth:web', 'throttle:60,1', RecordAuditLog::class])
	->name('content.api.v1.admin.')
	->group(function (): void {
		Route::get('/health', [ContentHealthController::class, 'admin'])->name('health');
	});
