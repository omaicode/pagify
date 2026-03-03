<?php

namespace Modules\Core\Providers;

use Throwable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Modules\Core\Models\AuditLog;
use Modules\Core\Models\Setting;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\Site;
use Modules\Core\Policies\AuditLogPolicy;
use Modules\Core\Policies\SettingPolicy;
use Modules\Core\Policies\SitePolicy;
use Modules\Core\Services\AuditLogger;
use Modules\Core\Services\ModuleRegistry;
use Modules\Core\Services\SettingsManager;
use Modules\Core\Support\SiteContext;

class CoreServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->mergeConfigFrom(__DIR__ . '/../../config/core.php', 'core');

		$this->app->singleton(SiteContext::class);

		$this->app->singleton(ModuleRegistry::class, function (): ModuleRegistry {
			return new ModuleRegistry(config('core.modules', []));
		});

		$this->app->alias(ModuleRegistry::class, 'core.modules');

		$this->app->singleton(SettingsManager::class, function ($app): SettingsManager {
			return new SettingsManager($app->make(SiteContext::class));
		});

		$this->app->alias(SettingsManager::class, 'core.settings');

		$this->app->singleton(AuditLogger::class);
		$this->app->alias(AuditLogger::class, 'core.audit');
	}

	public function boot(): void
	{
		$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
		$this->loadRoutesFrom(__DIR__ . '/../../routes/core-routes.php');
		$this->loadViewsFrom(__DIR__ . '/../../resources/views', 'core');
		$this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'core');

		$this->publishes([
			__DIR__ . '/../../config/core.php' => config_path('core.php'),
		], 'core-config');

		$this->registerSiteContextFromRequest();
		$this->registerPolicies();
	}

	private function registerSiteContextFromRequest(): void
	{
		$siteContext = $this->app->make(SiteContext::class);

		Route::matched(static function () use ($siteContext): void {
			if ($siteContext->hasSite()) {
				return;
			}

			$host = request()->getHost();

			if ($host === '') {
				return;
			}

			try {
				$site = Site::query()->where('domain', $host)->first();
			} catch (Throwable) {
				return;
			}

			if ($site !== null) {
				$siteContext->setSite($site);
			}
		});
	}

	private function registerPolicies(): void
	{
		Gate::policy(Site::class, SitePolicy::class);
		Gate::policy(Setting::class, SettingPolicy::class);
		Gate::policy(AuditLog::class, AuditLogPolicy::class);
	}
}
