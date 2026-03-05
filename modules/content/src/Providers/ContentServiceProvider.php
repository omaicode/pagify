<?php

namespace Pagify\Content\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Pagify\Content\Console\Commands\ProcessScheduledContentCommand;
use Pagify\Content\Models\ContentEntry;
use Pagify\Content\Models\ContentType;
use Pagify\Content\Policies\ContentEntryPolicy;
use Pagify\Content\Policies\ContentTypePolicy;

class ContentServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->mergeConfigFrom(__DIR__ . '/../../config/content.php', 'content');
		$this->mergeConfigFrom(__DIR__ . '/../../config/module.php', 'content.module');
		$this->mergeConfigFrom(__DIR__ . '/../../config/menu.php', 'content.menu');
	}

	public function boot(): void
	{
		$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
		$this->loadRoutesFrom(__DIR__ . '/../../routes/content-routes.php');
		$this->loadViewsFrom(__DIR__ . '/../../resources/views', 'content');
		$this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'content');

		$this->publishes([
			__DIR__ . '/../../config/content.php' => config_path('content.php'),
			__DIR__ . '/../../config/module.php' => config_path('content-module.php'),
			__DIR__ . '/../../config/menu.php' => config_path('content-menu.php'),
		], 'content-config');

		Gate::policy(ContentType::class, ContentTypePolicy::class);
		Gate::policy(ContentEntry::class, ContentEntryPolicy::class);

		if ($this->app->runningInConsole()) {
			$this->commands([
				ProcessScheduledContentCommand::class,
			]);
		}
	}
}
