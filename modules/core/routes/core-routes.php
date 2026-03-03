<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\Admin\AuthController;
use Modules\Core\Http\Middleware\ResolveSite;
use Modules\Core\Http\Middleware\SetLocaleFromSite;
use Modules\Core\Services\ModuleRegistry;
use Modules\Core\Support\SiteContext;

Route::middleware(['web', ResolveSite::class, SetLocaleFromSite::class])->group(function (): void {
    Route::get('/', function () {
        return view('core::index');
    })->name('core.index');

    Route::prefix('admin')->name('core.admin.')->group(function (): void {
        Route::middleware('guest:web')->group(function (): void {
            Route::get('/login', [AuthController::class, 'create'])->name('login');
            Route::post('/login', [AuthController::class, 'store'])->name('login.store');
        });

        Route::post('/logout', [AuthController::class, 'destroy'])
            ->middleware('auth:web')
            ->name('logout');

        Route::middleware('auth:web')->group(function (): void {
        Route::get('/', function (ModuleRegistry $modules, SiteContext $siteContext) {
            return response()->json([
                'module' => 'core',
                'menu' => $modules->adminMenu(),
                'site' => $siteContext->site()?->only(['id', 'name', 'domain', 'locale']),
                'locale' => app()->getLocale(),
            ]);
        })->name('dashboard');

        Route::get('/audit-logs', function () {
            return response()->json([
                'message' => 'Audit log UI scaffold is ready.',
            ]);
        })->name('audit.index');
        });
    });
});

Route::prefix('api/v1')
    ->middleware(['api', ResolveSite::class, SetLocaleFromSite::class, 'auth:sanctum', 'throttle:60,1'])
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
