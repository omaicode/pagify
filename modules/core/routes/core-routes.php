<?php

use Illuminate\Support\Facades\Route;
use Pagify\Core\Http\Controllers\Admin\AuditLogController;
use Pagify\Core\Http\Controllers\Admin\ApiTokenPageController;
use Pagify\Core\Http\Controllers\Admin\AuthController;
use Pagify\Core\Http\Controllers\Admin\DashboardController;
use Pagify\Core\Http\Controllers\Admin\LocaleController;
use Pagify\Core\Http\Controllers\Admin\ModulePageController;
use Pagify\Core\Http\Controllers\Admin\ProfilePageController;
use Pagify\Core\Http\Controllers\Admin\SettingsPageController;
use Pagify\Core\Http\Controllers\Api\AdminProfileController;
use Pagify\Core\Http\Controllers\Api\AdminModuleController;
use Pagify\Core\Http\Controllers\Api\AdminTokenController;
use Pagify\Core\Http\Middleware\EnsureApiErrorEnvelope;
use Pagify\Core\Http\Middleware\HandleInertiaRequests;
use Pagify\Core\Http\Middleware\RecordAuditLog;
use Pagify\Core\Http\Middleware\ResolveSite;
use Pagify\Core\Http\Middleware\SetLocaleFromSite;
use Pagify\Core\Support\SiteContext;

Route::middleware(['web', ResolveSite::class, SetLocaleFromSite::class])->group(function (): void {
    Route::get('/', function () {
        return view('core::index');
    })->name('core.index');

    Route::prefix(config('app.admin_url_prefix'))->name('core.admin.')->group(function (): void {
        Route::middleware('guest:web')->group(function (): void {
            Route::get('/login', [AuthController::class, 'create'])
                ->middleware(HandleInertiaRequests::class)
                ->name('login');
            Route::post('/login', [AuthController::class, 'store'])->name('login.store');
        });

        Route::post('/logout', [AuthController::class, 'destroy'])
            ->middleware('auth:web')
            ->name('logout');

        Route::middleware(['auth:web', HandleInertiaRequests::class, RecordAuditLog::class])->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('/api-tokens', ApiTokenPageController::class)->name('tokens.index');
        Route::get('/modules', ModulePageController::class)->name('modules.index');
        Route::get('/profile', ProfilePageController::class)->name('profile.index');
        Route::get('/settings', SettingsPageController::class)->name('settings.index');
        });
    });
});

Route::prefix('api/v1')
    ->middleware(['api', EnsureApiErrorEnvelope::class, ResolveSite::class, SetLocaleFromSite::class, 'auth:sanctum', 'throttle:60,1'])
    ->name('core.api.v1.')
    ->group(function (): void {
        Route::get('/health', function (SiteContext $siteContext) {
            return response()->json([
                'success' => true,
                'module' => 'core',
                'site_id' => $siteContext->siteId(),
                'locale' => app()->getLocale(),
            ]);
        })->name('health');
    });

Route::prefix('api/v1/'.config('app.admin_url_prefix'))
    ->middleware(['web', EnsureApiErrorEnvelope::class, ResolveSite::class, SetLocaleFromSite::class, 'auth:web', 'throttle:60,1', RecordAuditLog::class])
    ->name('core.api.v1.admin.')
    ->group(function (): void {
        Route::get('/tokens', [AdminTokenController::class, 'index'])->name('tokens.index');
        Route::post('/tokens', [AdminTokenController::class, 'store'])->name('tokens.store');
        Route::delete('/tokens/{tokenId}', [AdminTokenController::class, 'destroy'])->name('tokens.destroy');

        Route::patch('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password.update');
        Route::post('/profile/avatar', [AdminProfileController::class, 'storeAvatar'])->name('profile.avatar.store');
        Route::delete('/profile/avatar', [AdminProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');

        Route::get('/modules', [AdminModuleController::class, 'index'])->name('modules.index');
        Route::patch('/modules/{module}', [AdminModuleController::class, 'update'])->name('modules.update');
        Route::get('/modules/health', [AdminModuleController::class, 'health'])->name('modules.health');
    });
