<?php

namespace Pagify\Updater\Providers;

use Illuminate\Support\ServiceProvider;
use Pagify\Updater\Console\Commands\UpdaterAllCommand;
use Pagify\Updater\Console\Commands\UpdaterModuleCommand;
use Pagify\Updater\Console\Commands\UpdaterRollbackCommand;
use Pagify\Updater\Services\ComposerCommandRunner;
use Pagify\Updater\Services\ModulePackageResolver;
use Pagify\Updater\Services\UpdaterExecutionService;

class UpdaterServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->mergeConfigFrom(__DIR__ . '/../../config/updater.php', 'updater');
		$this->mergeConfigFrom(__DIR__ . '/../../config/module.php', 'updater.module');
		$this->mergeConfigFrom(__DIR__ . '/../../config/menu.php', 'updater.menu');

		$this->app->singleton(ModulePackageResolver::class);
		$this->app->singleton(ComposerCommandRunner::class);
		$this->app->singleton(UpdaterExecutionService::class);
	}

	public function boot(): void
	{
		$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
		$this->loadRoutesFrom(__DIR__ . '/../../routes/updater-routes.php');

		$this->publishes([
			__DIR__ . '/../../config/updater.php' => config_path('updater.php'),
			__DIR__ . '/../../config/module.php' => config_path('updater-module.php'),
			__DIR__ . '/../../config/menu.php' => config_path('updater-menu.php'),
		], 'updater-config');

		if ($this->app->runningInConsole()) {
			$this->commands([
				UpdaterModuleCommand::class,
				UpdaterAllCommand::class,
				UpdaterRollbackCommand::class,
			]);
		}
	}
}
