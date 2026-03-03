<?php

namespace Modules\Content\Providers;

use Illuminate\Support\ServiceProvider;

class ContentServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->mergeConfigFrom(__DIR__ . '/../../config/content.php', 'content');
	}

	public function boot(): void
	{
		$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
		$this->loadRoutesFrom(__DIR__ . '/../../routes/content-routes.php');
		$this->loadViewsFrom(__DIR__ . '/../../resources/views', 'content');

		$this->publishes([
			__DIR__ . '/../../config/content.php' => config_path('content.php'),
		], 'content-config');
	}
}
