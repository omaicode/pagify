<?php

namespace Pagify\Installer\Providers;

use Illuminate\Support\ServiceProvider;
use Pagify\Installer\Services\ConnectivityService;
use Pagify\Installer\Services\EnvironmentWriter;
use Pagify\Installer\Services\InstallerStateService;
use Pagify\Installer\Services\InstallerThemeInstallerService;
use Pagify\Installer\Services\MarketplaceCatalogService;
use Pagify\Installer\Services\SystemCheckService;

class InstallerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/installer.php', 'installer');
        $this->mergeConfigFrom(__DIR__.'/../../config/module.php', 'installer.module');

        $this->app->singleton(InstallerStateService::class);
        $this->app->singleton(SystemCheckService::class);
        $this->app->singleton(ConnectivityService::class);
        $this->app->singleton(EnvironmentWriter::class);
        $this->app->singleton(MarketplaceCatalogService::class);
        $this->app->singleton(InstallerThemeInstallerService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/installer-routes.php');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'installer');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'installer');

        $this->publishes([
            __DIR__.'/../../config/installer.php' => config_path('installer.php'),
            __DIR__.'/../../config/module.php' => config_path('installer-module.php'),
        ], 'installer-config');
    }
}
