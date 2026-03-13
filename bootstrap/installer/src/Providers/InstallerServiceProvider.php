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

        $this->forceSafeSessionDriverDuringInstallation();
        $this->forceSafeCacheStoreDuringInstallation();

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

    private function forceSafeSessionDriverDuringInstallation(): void
    {
        if ($this->isInstallationCompleted()) {
            return;
        }

        $driver = (string) config('session.driver', env('SESSION_DRIVER', 'database'));

        if ($driver === '' || $driver === 'database') {
            config(['session.driver' => 'file']);
        }
    }

    private function forceSafeCacheStoreDuringInstallation(): void
    {
        if ($this->isInstallationCompleted()) {
            return;
        }

        $store = (string) config('cache.default', env('CACHE_STORE', 'database'));

        if ($store === '' || $store === 'database') {
            config(['cache.default' => 'file']);
        }
    }

    private function isInstallationCompleted(): bool
    {
        $installedFile = (string) config('installer.state.installed_file', storage_path('app/installer/installed.lock'));

        if (is_file($installedFile)) {
            return true;
        }

        $stateFile = (string) config('installer.state.state_file', storage_path('app/installer/state.json'));

        if (! is_file($stateFile)) {
            return false;
        }

        $decoded = json_decode((string) file_get_contents($stateFile), true);

        return is_array($decoded) && (bool) ($decoded['installed'] ?? false);
    }
}
