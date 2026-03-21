<?php

namespace Pagify\PageBuilder\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Pagify\Core\Services\EventBus;
use Pagify\PageBuilder\Console\Commands\ValidateWebstudioComponentsCommand;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Policies\PagePolicy;
use Pagify\PageBuilder\Services\WebstudioComponentDefinitionDiscoveryService;

class PageBuilderServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->mergeConfigFrom(__DIR__ . '/../../config/page-builder.php', 'page-builder');
		$this->mergeConfigFrom(__DIR__ . '/../../config/module.php', 'page-builder.module');
		$this->mergeConfigFrom(__DIR__ . '/../../config/menu.php', 'page-builder.menu');
	}

	public function boot(): void
	{
		$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
		$this->loadRoutesFrom(__DIR__ . '/../../routes/page-builder-routes.php');
		$this->loadViewsFrom(__DIR__ . '/../../resources/views', 'page-builder');
		$this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'page-builder');

		$this->publishes([
			__DIR__ . '/../../config/page-builder.php' => config_path('page-builder.php'),
			__DIR__ . '/../../config/module.php' => config_path('page-builder-module.php'),
			__DIR__ . '/../../config/menu.php' => config_path('page-builder-menu.php'),
		], 'page-builder-config');

		Gate::policy(Page::class, PagePolicy::class);

		$eventBus = $this->app->make(EventBus::class);
		$discovery = $this->app->make(WebstudioComponentDefinitionDiscoveryService::class);

		$eventBus->onHook('page-builder.webstudio.components', static function () use ($discovery): array {
			return $discovery->discover();
		});

		if ($this->app->runningInConsole()) {
			$this->commands([
				ValidateWebstudioComponentsCommand::class,
			]);
		}
	}
}
