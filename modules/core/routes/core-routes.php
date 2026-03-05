<?php

use Illuminate\Support\Facades\Route;
use Pagify\Core\Http\Controllers\Admin\AdminGroupPageController;
use Pagify\Core\Http\Controllers\Admin\AdminManagerPageController;
use Pagify\Core\Http\Controllers\Admin\AuditLogController;
use Pagify\Core\Http\Controllers\Admin\ApiTokenPageController;
use Pagify\Core\Http\Controllers\Admin\AuthController;
use Pagify\Core\Http\Controllers\Admin\DashboardController;
use Pagify\Core\Http\Controllers\Admin\LocaleController;
use Pagify\Core\Http\Controllers\Admin\ModulePageController;
use Pagify\Core\Http\Controllers\Admin\PluginPageController;
use Pagify\Core\Http\Controllers\Admin\PermissionPageController;
use Pagify\Core\Http\Controllers\Admin\ProfilePageController;
use Pagify\Core\Http\Controllers\Admin\SettingsPageController;
use Pagify\Core\Http\Controllers\Api\AdminGroupController;
use Pagify\Core\Http\Controllers\Api\AdminManagerController;
use Pagify\Core\Http\Controllers\Api\AdminPermissionController;
use Pagify\Core\Http\Controllers\Api\AdminProfileController;
use Pagify\Core\Http\Controllers\Api\AdminModuleController;
use Pagify\Core\Http\Controllers\Api\AdminPluginController;
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
            Route::get('/permissions', PermissionPageController::class)->name('permissions.index');
            Route::get('/admin-groups', AdminGroupPageController::class)->name('admin-groups.index');
            Route::get('/admins', AdminManagerPageController::class)->name('admins.index');
            Route::get('/modules', ModulePageController::class)->name('modules.index');
            Route::get('/plugins', PluginPageController::class)->name('plugins.index');
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

        Route::get('/permissions', [AdminPermissionController::class, 'index'])->name('permissions.index');
        Route::post('/permissions', [AdminPermissionController::class, 'store'])->name('permissions.store');
        Route::patch('/permissions/{permissionId}', [AdminPermissionController::class, 'update'])->name('permissions.update');
        Route::delete('/permissions/{permissionId}', [AdminPermissionController::class, 'destroy'])->name('permissions.destroy');

        Route::get('/admin-groups', [AdminGroupController::class, 'index'])->name('admin-groups.index');
        Route::post('/admin-groups', [AdminGroupController::class, 'store'])->name('admin-groups.store');
        Route::patch('/admin-groups/{roleId}', [AdminGroupController::class, 'update'])->name('admin-groups.update');
        Route::delete('/admin-groups/{roleId}', [AdminGroupController::class, 'destroy'])->name('admin-groups.destroy');

        Route::get('/admins', [AdminManagerController::class, 'index'])->name('admins.index');
        Route::post('/admins', [AdminManagerController::class, 'store'])->name('admins.store');
        Route::patch('/admins/{managedAdminId}', [AdminManagerController::class, 'update'])->name('admins.update');
        Route::delete('/admins/{managedAdminId}', [AdminManagerController::class, 'destroy'])->name('admins.destroy');

        Route::patch('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password.update');
        Route::post('/profile/avatar', [AdminProfileController::class, 'storeAvatar'])->name('profile.avatar.store');
        Route::delete('/profile/avatar', [AdminProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');

        Route::get('/modules', [AdminModuleController::class, 'index'])->name('modules.index');
        Route::patch('/modules/{module}', [AdminModuleController::class, 'update'])->name('modules.update');
        Route::get('/modules/health', [AdminModuleController::class, 'health'])->name('modules.health');

        Route::get('/plugins', [AdminPluginController::class, 'index'])->name('plugins.index');
        Route::patch('/plugins/{plugin}', [AdminPluginController::class, 'update'])->name('plugins.update');
        Route::post('/plugins/install/composer', [AdminPluginController::class, 'installComposer'])->name('plugins.install.composer');
        Route::post('/plugins/install/zip', [AdminPluginController::class, 'installZip'])->name('plugins.install.zip');
        Route::delete('/plugins/{plugin}', [AdminPluginController::class, 'destroy'])->name('plugins.destroy');
        Route::get('/plugins/extensions', [AdminPluginController::class, 'extensions'])->name('plugins.extensions');
    });
