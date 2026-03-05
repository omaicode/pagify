<?php

namespace Pagify\Media\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Pagify\Media\Models\MediaAsset;
use Pagify\Media\Policies\MediaAssetPolicy;

class MediaServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->mergeConfigFrom(__DIR__ . '/../../config/media.php', 'media');
		$this->mergeConfigFrom(__DIR__ . '/../../config/module.php', 'media.module');
		$this->mergeConfigFrom(__DIR__ . '/../../config/menu.php', 'media.menu');
	}

	public function boot(): void
	{
		$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
		$this->loadRoutesFrom(__DIR__ . '/../../routes/media-routes.php');
		$this->loadViewsFrom(__DIR__ . '/../../resources/views', 'media');
		$this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'media');

		$this->publishes([
			__DIR__ . '/../../config/media.php' => config_path('media.php'),
			__DIR__ . '/../../config/module.php' => config_path('media-module.php'),
			__DIR__ . '/../../config/menu.php' => config_path('media-menu.php'),
		], 'media-config');

		Gate::policy(MediaAsset::class, MediaAssetPolicy::class);
	}
}
