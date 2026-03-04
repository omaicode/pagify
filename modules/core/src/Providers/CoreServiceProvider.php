<?php

namespace Modules\Core\Providers;

use Throwable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Modules\Core\Events\EntryCreated;
use Modules\Core\Events\EntryPublished;
use Modules\Core\Contracts\CoreHookSubscriber;
use Modules\Core\Console\Commands\CleanupAuditLogsCommand;
use Modules\Core\Models\Admin;
use Modules\Core\Models\AuditLog;
use Modules\Core\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\Site;
use Modules\Core\Observers\AuditableModelObserver;
use Modules\Core\Policies\AuditLogPolicy;
use Modules\Core\Policies\AdminPolicy;
use Modules\Core\Policies\SettingPolicy;
use Modules\Core\Policies\SitePolicy;
use Modules\Core\Services\AuditLogger;
use Modules\Core\Services\EventBus;
use Modules\Core\Services\HookRegistry;
use Modules\Core\Services\ModuleRegistry;
use Modules\Core\Services\ModuleStateService;
use Modules\Core\Services\SettingsManager;
use Modules\Core\Support\SiteContext;
use JsonException;

class CoreServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->mergeConfigFrom(__DIR__ . '/../../config/core.php', 'core');

		$this->mergeModuleUiContractsIntoConfig();

		$this->app->singleton(SiteContext::class);

		$this->app->singleton(ModuleRegistry::class, function (): ModuleRegistry {
			return new ModuleRegistry(
				config('core.modules', []),
				$this->app->make(ModuleStateService::class),
			);
		});

		$this->app->alias(ModuleRegistry::class, 'core.modules');

		$this->app->singleton(ModuleStateService::class, function ($app): ModuleStateService {
			return new ModuleStateService($app->make('cache.store'));
		});

		$this->app->alias(ModuleStateService::class, 'core.module-state');

		$this->app->singleton(SettingsManager::class, function ($app): SettingsManager {
			return new SettingsManager($app->make(SiteContext::class));
		});

		$this->app->alias(SettingsManager::class, 'core.settings');

		$this->app->singleton(AuditLogger::class);
		$this->app->alias(AuditLogger::class, 'core.audit');

		$this->app->singleton(HookRegistry::class);
		$this->app->alias(HookRegistry::class, 'core.hooks');

		$this->app->singleton(EventBus::class, function ($app): EventBus {
			return new EventBus(
				dispatcher: $app['events'],
				hooks: $app->make(HookRegistry::class),
			);
		});

		$this->app->alias(EventBus::class, 'core.event-bus');
	}

	private function mergeModuleUiContractsIntoConfig(): void
	{
		$modules = config('core.modules', []);

		if (! is_array($modules) || $modules === []) {
			return;
		}

		$resolvedModules = [];

		foreach ($modules as $moduleKey => $moduleConfig) {
			if (! is_array($moduleConfig)) {
				continue;
			}

			$contractPath = $moduleConfig['ui_contract_path'] ?? null;

			if (! is_string($contractPath) || $contractPath === '' || ! is_file($contractPath)) {
				$resolvedModules[$moduleKey] = $moduleConfig;
				continue;
			}

			$payload = file_get_contents($contractPath);

			if (! is_string($payload) || trim($payload) === '') {
				$resolvedModules[$moduleKey] = $moduleConfig;
				continue;
			}

			try {
				/** @var array<string, mixed> $decoded */
				$decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
			} catch (JsonException) {
				$resolvedModules[$moduleKey] = $moduleConfig;
				continue;
			}

			$contractMenus = $decoded['menus'] ?? [];
			$contractPages = $decoded['pages'] ?? [];
			$contractActions = $decoded['actions'] ?? [];
			$contractWidgets = $decoded['widgets'] ?? [];

			$resolvedModules[$moduleKey] = [
				...$moduleConfig,
				'menu' => is_array($contractMenus) ? $contractMenus : ($moduleConfig['menu'] ?? []),
				'pages' => is_array($contractPages) ? $contractPages : [],
				'actions' => is_array($contractActions) ? $contractActions : [],
				'widgets' => is_array($contractWidgets) ? $contractWidgets : [],
				'ui_contract' => [
					'path' => $contractPath,
					'loaded' => true,
				],
			];
		}

		config(['core.modules' => $resolvedModules]);
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
		$this->registerEventHookBridges();
		$this->registerConfiguredHookSubscribers();
		$this->registerAuditObservers();

		if ($this->app->runningInConsole()) {
			$this->commands([
				CleanupAuditLogsCommand::class,
			]);
		}
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
				$site = Site::query()
					->where('domain', $host)
					->where('is_active', true)
					->first();
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
		Gate::policy(Admin::class, AdminPolicy::class);
		Gate::policy(Site::class, SitePolicy::class);
		Gate::policy(Setting::class, SettingPolicy::class);
		Gate::policy(AuditLog::class, AuditLogPolicy::class);
	}

	private function registerEventHookBridges(): void
	{
		$eventBus = $this->app->make(EventBus::class);

		$eventBus->bridgeEventToHook(EntryCreated::class, 'entry.created');
		$eventBus->bridgeEventToHook(EntryPublished::class, 'entry.published');
	}

	private function registerConfiguredHookSubscribers(): void
	{
		$eventBus = $this->app->make(EventBus::class);
		$subscribers = config('core.hook_subscribers', []);

		if (! is_array($subscribers)) {
			return;
		}

		foreach ($subscribers as $subscriberClass) {
			if (! is_string($subscriberClass) || $subscriberClass === '') {
				continue;
			}

			if (! class_exists($subscriberClass)) {
				continue;
			}

			$subscriber = $this->app->make($subscriberClass);

			if (! $subscriber instanceof CoreHookSubscriber) {
				continue;
			}

			$eventBus->registerSubscriber($subscriber);
		}
	}

	private function registerAuditObservers(): void
	{
		$models = config('core.audit.audited_models', []);

		if (! is_array($models)) {
			return;
		}

		$observer = $this->app->make(AuditableModelObserver::class);

		foreach ($models as $modelClass) {
			if (! is_string($modelClass) || $modelClass === '') {
				continue;
			}

			if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
				continue;
			}

			$modelClass::observe($observer);
		}
	}
}
