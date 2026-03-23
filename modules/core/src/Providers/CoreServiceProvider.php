<?php

namespace Pagify\Core\Providers;

use Throwable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Pagify\Core\Events\EntryCreated;
use Pagify\Core\Events\EntryPublished;
use Pagify\Core\Contracts\CoreHookSubscriber;
use Pagify\Core\Console\Commands\CleanupAuditLogsCommand;
use Pagify\Core\Console\Commands\MakePluginCommand;
use Pagify\Core\Console\Commands\MakeThemeCommand;
use Pagify\Core\Models\Admin;
use Pagify\Core\Models\AuditLog;
use Pagify\Core\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Pagify\Core\Models\Site;
use Pagify\Core\Observers\AuditableModelObserver;
use Pagify\Core\Policies\AuditLogPolicy;
use Pagify\Core\Policies\AdminPolicy;
use Pagify\Core\Policies\SettingPolicy;
use Pagify\Core\Policies\SitePolicy;
use Pagify\Core\Services\AuditLogger;
use Pagify\Core\Services\EventBus;
use Pagify\Core\Services\HookRegistry;
use Pagify\Core\Services\ModuleRegistry;
use Pagify\Core\Services\ModuleStateService;
use Pagify\Core\Services\PluginCompatibilityService;
use Pagify\Core\Services\PluginExtensionRegistry;
use Pagify\Core\Services\PluginManagerService;
use Pagify\Core\Services\PluginManifestService;
use Pagify\Core\Services\SettingsManager;
use Pagify\Core\Support\SiteContext;

class CoreServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->mergeConfigFrom(__DIR__ . '/../../config/core.php', 'core');
		$this->mergeConfigFrom(__DIR__ . '/../../config/mail-ui.php', 'core_mail_ui');
		$this->mergeConfigFrom(__DIR__ . '/../../config/menu.php', 'core.menu');
		$this->mergeConfigFrom(__DIR__ . '/../../config/module.php', 'core.module');
		$this->mergeConfigFrom(__DIR__ . '/../../config/plugins.php', 'plugins');

		$this->app->singleton(SiteContext::class);

		$this->app->singleton(ModuleRegistry::class, function (): ModuleRegistry {
			$modules = $this->resolveRegisteredModules();

			return new ModuleRegistry(
				$modules,
				$this->app->make(ModuleStateService::class),
				moduleMenus: $this->resolveModuleMenus($modules),
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

		$this->app->singleton(PluginManifestService::class);
		$this->app->singleton(PluginCompatibilityService::class);
		$this->app->singleton(PluginManagerService::class);
		$this->app->singleton(PluginExtensionRegistry::class);

		$this->app->alias(PluginManagerService::class, 'core.plugins');
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function resolveRegisteredModules(): array
	{
		$resolvedModules = [];

		foreach ($this->discoverModuleSlugs() as $moduleSlug) {
			$moduleConfig = config(sprintf('%s.module', $moduleSlug), []);

			if (! is_array($moduleConfig) || $moduleConfig === []) {
				$moduleConfig = $this->loadModuleConfigFromFile($moduleSlug);
			}

			if (! is_array($moduleConfig) || $moduleConfig === []) {
				continue;
			}

			$resolvedSlug = (string) ($moduleConfig['slug'] ?? $moduleSlug);

			if ($resolvedSlug === '') {
				continue;
			}

			$resolvedModules[$resolvedSlug] = [
				...$moduleConfig,
				'slug' => $resolvedSlug,
			];
		}

		return $resolvedModules;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function loadModuleConfigFromFile(string $moduleSlug): array
	{
		$path = base_path(sprintf('modules/%s/config/module.php', $moduleSlug));

		if (! is_file($path)) {
			return [];
		}

		$data = require $path;

		return is_array($data) ? $data : [];
	}

	/**
	 * @return array<int, string>
	 */
	private function discoverModuleSlugs(): array
	{
		$moduleConfigFiles = glob(base_path('modules/*/config/module.php'));

		if (! is_array($moduleConfigFiles) || $moduleConfigFiles === []) {
			return [];
		}

		$slugs = array_values(array_unique(array_filter(array_map(
			static function (string $path): string {
				return basename(dirname(dirname($path)));
			},
			$moduleConfigFiles,
		), static fn (string $slug): bool => $slug !== '')));

		sort($slugs);

		return $slugs;
	}

	/**
	 * @param array<string, array<string, mixed>> $modules
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	private function resolveModuleMenus(array $modules): array
	{
		$resolved = [];

		foreach ($modules as $moduleKey => $moduleConfig) {
			if (! is_array($moduleConfig)) {
				continue;
			}

			$configKey = $moduleConfig['config_key'] ?? $moduleConfig['slug'] ?? $moduleKey;

			if (! is_string($configKey) || trim($configKey) === '') {
				continue;
			}

			$menu = config(sprintf('%s.menu', $configKey), []);

			if (is_array($menu)) {
				$resolved[(string) $moduleKey] = $menu;
			}
		}

		return $resolved;
	}

	public function boot(): void
	{
		$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
		$this->loadRoutesFrom(__DIR__ . '/../../routes/core-routes.php');
		$this->loadViewsFrom($this->adminThemeViewPaths(), 'core');
		$this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'core');

		$this->publishes([
			__DIR__ . '/../../config/core.php' => config_path('core.php'),
			__DIR__ . '/../../config/mail-ui.php' => config_path('core-mail-ui.php'),
			__DIR__ . '/../../config/module.php' => config_path('core-module.php'),
			__DIR__ . '/../../config/menu.php' => config_path('core-menu.php'),
		], 'core-config');

		$this->registerSiteContextFromRequest();
		$this->registerPolicies();
		$this->registerEventHookBridges();
		$this->registerConfiguredHookSubscribers();
		$this->registerPluginRuntimeContributions();
		$this->applyPluginFieldTypeExtensions();
		$this->registerAuditObservers();

		if ($this->app->runningInConsole()) {
			$this->commands([
				CleanupAuditLogsCommand::class,
				MakePluginCommand::class,
				MakeThemeCommand::class,
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

	private function registerPluginRuntimeContributions(): void
	{
		$pluginManager = $this->app->make(PluginManagerService::class);
		$eventBus = $this->app->make(EventBus::class);

		foreach ($pluginManager->all() as $plugin) {
			if (! ((bool) ($plugin['enabled'] ?? false)) || ! ((bool) ($plugin['is_compatible'] ?? false))) {
				continue;
			}

			$manifest = $plugin['manifest'] ?? [];

			if (! is_array($manifest)) {
				continue;
			}

			$providers = $manifest['providers'] ?? [];

			if (is_array($providers)) {
				foreach ($providers as $providerClass) {
					if (! is_string($providerClass) || $providerClass === '' || ! class_exists($providerClass)) {
						continue;
					}

					try {
						$this->app->register($providerClass);
					} catch (Throwable $exception) {
						report($exception);
						$pluginManager->disablePluginFromThrowable($exception);
					}
				}
			}

			$hookSubscribers = $manifest['hooks']['subscribers'] ?? [];

			if (is_array($hookSubscribers)) {
				foreach ($hookSubscribers as $subscriberClass) {
					if (! is_string($subscriberClass) || $subscriberClass === '' || ! class_exists($subscriberClass)) {
						continue;
					}

					$subscriber = $this->app->make($subscriberClass);

					if ($subscriber instanceof CoreHookSubscriber) {
						$eventBus->registerSubscriber($subscriber);
					}
				}
			}
		}
	}

	private function applyPluginFieldTypeExtensions(): void
	{
		$fieldTypes = config('content.field_types', []);

		if (! is_array($fieldTypes)) {
			$fieldTypes = [];
		}

		$extensions = $this->app->make(PluginExtensionRegistry::class)->fieldTypes();
		config()->set('content.field_types', array_values(array_unique(array_merge($fieldTypes, $extensions))));
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

	/**
	 * @return array<int, string>
	 */
	private function adminThemeViewPaths(): array
	{
		$themesBasePath = trim((string) config('core.admin_ui.themes_base_path', 'themes/admin'), '/');
		$activeTheme = trim((string) config('core.admin_ui.theme', 'default'), '/');
		$fallbackTheme = trim((string) config('core.admin_ui.fallback_theme', 'default'), '/');

		$themes = array_values(array_unique(array_filter([
			$activeTheme,
			$fallbackTheme,
		], static fn (string $theme): bool => $theme !== '')));

		$paths = [];

		foreach ($themes as $theme) {
			$themeViewPath = base_path(sprintf('%s/%s/resources/views', $themesBasePath, $theme));

			if (is_dir($themeViewPath)) {
				$paths[] = $themeViewPath;
			}
		}

		$paths[] = __DIR__ . '/../../resources/views';

		return $paths;
	}
}
