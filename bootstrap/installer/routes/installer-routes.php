<?php

use Illuminate\Support\Facades\Route;
use Pagify\Installer\Http\Controllers\Api\InstallerWizardController;
use Pagify\Installer\Http\Controllers\InstallerWizardPageController;

Route::middleware(['web'])->group(function (): void {
    Route::get('/install', InstallerWizardPageController::class)->name('installer.wizard');
});

Route::prefix('api/v1/install')
    ->middleware(['web', 'throttle:60,1'])
    ->name('installer.api.v1.')
    ->group(function (): void {
        Route::get('/state', [InstallerWizardController::class, 'state'])->name('state');
        Route::get('/checks', [InstallerWizardController::class, 'checks'])->name('checks');
        Route::post('/configuration', [InstallerWizardController::class, 'storeConfiguration'])->name('configuration');
        Route::post('/purpose', [InstallerWizardController::class, 'storePurpose'])->name('purpose');
        Route::get('/plugins', [InstallerWizardController::class, 'plugins'])->name('plugins');
        Route::post('/plugins/install', [InstallerWizardController::class, 'installPlugins'])->name('plugins.install');
        Route::post('/plugins/skip', [InstallerWizardController::class, 'skipPlugins'])->name('plugins.skip');
        Route::get('/themes', [InstallerWizardController::class, 'themes'])->name('themes');
        Route::post('/themes/install', [InstallerWizardController::class, 'installThemes'])->name('themes.install');
        Route::post('/themes/skip', [InstallerWizardController::class, 'skipThemes'])->name('themes.skip');
        Route::post('/preflight', [InstallerWizardController::class, 'preflight'])->name('preflight');
        Route::post('/finalize', [InstallerWizardController::class, 'finalize'])->name('finalize');
    });
