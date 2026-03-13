<?php

namespace Pagify\Installer\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Pagify\Core\Models\Admin;
use Pagify\Core\Models\Site;
use Pagify\Core\Services\FrontendThemeManagerService;
use Pagify\Core\Services\PluginManagerService;
use Pagify\Installer\Http\Requests\FinalizeInstallationRequest;
use Pagify\Installer\Http\Requests\InstallSelectionRequest;
use Pagify\Installer\Http\Requests\StoreConfigurationRequest;
use Pagify\Installer\Http\Requests\StorePurposeRequest;
use Pagify\Installer\Services\ConnectivityService;
use Pagify\Installer\Services\EnvironmentWriter;
use Pagify\Installer\Services\InstallerStateService;
use Pagify\Installer\Services\InstallerThemeInstallerService;
use Pagify\Installer\Services\MarketplaceCatalogService;
use Pagify\Installer\Services\SystemCheckService;
use Throwable;

class InstallerWizardController extends InstallerApiController
{
    public function state(InstallerStateService $state): JsonResponse
    {
        return $this->success($state->state());
    }

    public function checks(SystemCheckService $checks, InstallerStateService $state): JsonResponse
    {
        $requestedLocale = substr((string) request()->header('Accept-Language', ''), 0, 2);
        if (in_array($requestedLocale, ['en', 'vi'], true)) {
            app()->setLocale($requestedLocale);
        }

        $this->logStep('checks', 'started');

        $result = $checks->run();

        if (($result['ok'] ?? false) === true) {
            $state->completeStep(1);
            $this->logStep('checks', 'passed', ['result' => $result]);
        } else {
            $this->logStep('checks', 'failed', ['result' => $result]);
        }

        return $this->success($result);
    }

    public function storeConfiguration(
        StoreConfigurationRequest $request,
        ConnectivityService $connectivity,
        EnvironmentWriter $envWriter,
        InstallerStateService $state
    ): JsonResponse {
        if (! $state->hasCompletedStep(1)) {
            $this->logStep('configuration', 'failed', ['reason' => 'step_blocked']);
            return $this->error('System check must pass before saving configuration.', 422, 'INSTALLER_STEP_BLOCKED');
        }

        $this->logStep('configuration', 'started');

        $payload = $request->validated();
        $db = (array) Arr::get($payload, 'db', []);
        $mail = (array) Arr::get($payload, 'mail', []);

        $dbTest = $connectivity->testDatabase($db);
        if (($dbTest['ok'] ?? false) !== true) {
            $this->logStep('configuration', 'failed', ['db' => $dbTest]);
            return $this->error((string) $dbTest['message'], 422, 'DB_CONNECTION_FAILED');
        }

        $mailTest = $connectivity->testMail($mail);
        if (($mailTest['ok'] ?? false) !== true) {
            $this->logStep('configuration', 'failed', ['mail' => $mailTest]);
            return $this->error((string) $mailTest['message'], 422, 'MAIL_CONNECTION_FAILED');
        }

        $envWriter->write([
            'APP_NAME' => (string) Arr::get($payload, 'project_name', 'Pagify'),
            'APP_URL' => (string) Arr::get($payload, 'app_url', URL::to('/')),
            'DB_CONNECTION' => (string) Arr::get($payload, 'db.connection', 'mysql'),
            'DB_HOST' => (string) Arr::get($payload, 'db.host', '127.0.0.1'),
            'DB_PORT' => (string) Arr::get($payload, 'db.port', '3306'),
            'DB_DATABASE' => (string) Arr::get($payload, 'db.database', ''),
            'DB_USERNAME' => (string) Arr::get($payload, 'db.username', ''),
            'DB_PASSWORD' => (string) Arr::get($payload, 'db.password', ''),
            'MAIL_MAILER' => (string) Arr::get($payload, 'mail.mailer', 'smtp'),
            'MAIL_HOST' => (string) Arr::get($payload, 'mail.host', '127.0.0.1'),
            'MAIL_PORT' => (string) Arr::get($payload, 'mail.port', '1025'),
            'MAIL_USERNAME' => (string) Arr::get($payload, 'mail.username', ''),
            'MAIL_PASSWORD' => (string) Arr::get($payload, 'mail.password', ''),
            'MAIL_ENCRYPTION' => (string) Arr::get($payload, 'mail.encryption', 'tls'),
            'MAIL_FROM_ADDRESS' => (string) Arr::get($payload, 'mail.from_address', 'hello@example.com'),
            'MAIL_FROM_NAME' => (string) Arr::get($payload, 'mail.from_name', Arr::get($payload, 'project_name', 'Pagify')),
        ]);

        $state->mergeState([
            'configuration' => $payload,
        ]);
        $state->completeStep(2);
        $this->logStep('configuration', 'passed');

        return $this->success([
            'db' => $dbTest,
            'mail' => $mailTest,
            'message' => 'Configuration saved and connectivity checks passed.',
        ]);
    }

    public function storePurpose(StorePurposeRequest $request, InstallerStateService $state): JsonResponse
    {
        if (! $state->hasCompletedStep(2)) {
            $this->logStep('purpose', 'failed', ['reason' => 'step_blocked']);
            return $this->error('Configuration step must pass before purpose selection.', 422, 'INSTALLER_STEP_BLOCKED');
        }

        $this->logStep('purpose', 'started');

        $payload = $request->validated();

        $state->mergeState([
            'purpose' => [
                'purpose' => (string) $payload['purpose'],
                'custom_purpose' => (string) ($payload['custom_purpose'] ?? ''),
            ],
        ]);

        $state->completeStep(3);
        $this->logStep('purpose', 'passed', ['purpose' => $payload['purpose'] ?? null]);

        return $this->success([
            'message' => 'Purpose saved.',
            'purpose' => $payload,
        ]);
    }

    public function plugins(InstallerStateService $state, MarketplaceCatalogService $catalog): JsonResponse
    {
        if (! $state->hasCompletedStep(3)) {
            $this->logStep('plugins', 'failed', ['reason' => 'step_blocked']);
            return $this->error('Purpose selection must be completed before plugin listing.', 422, 'INSTALLER_STEP_BLOCKED');
        }

        $purpose = (string) Arr::get($state->state(), 'purpose.purpose', 'other');

        return $this->success($catalog->plugins($purpose));
    }

    public function installPlugins(
        InstallSelectionRequest $request,
        InstallerStateService $state,
        MarketplaceCatalogService $catalog,
        PluginManagerService $plugins
    ): JsonResponse {
        if (! $state->hasCompletedStep(3)) {
            $this->logStep('plugins_install', 'failed', ['reason' => 'step_blocked']);
            return $this->error('Purpose selection must be completed before plugin install.', 422, 'INSTALLER_STEP_BLOCKED');
        }

        $this->logStep('plugins_install', 'started');

        $selectedSlugs = array_values(array_unique(array_map('strval', (array) $request->validated('slugs'))));
        $selectedItems = $catalog->findPluginPackagesBySlugs($selectedSlugs);

        if ($selectedItems === []) {
            $this->logStep('plugins_install', 'failed', ['reason' => 'invalid_selection', 'slugs' => $selectedSlugs]);
            return $this->error('No valid plugin selection found in catalog.', 422, 'PLUGIN_SELECTION_INVALID');
        }

        $results = [];
        $failed = false;

        foreach ($selectedItems as $item) {
            $slug = (string) ($item['slug'] ?? '');
            $packageName = (string) ($item['package_name'] ?? '');

            $result = $plugins->installComposerPackage($packageName);
            $ok = (bool) ($result['ok'] ?? false);

            $results[] = [
                'slug' => $slug,
                'package_name' => $packageName,
                'ok' => $ok,
                'message' => (string) ($result['message'] ?? ''),
            ];

            if (! $ok) {
                $failed = true;
            }
        }

        if ($failed) {
            $this->logStep('plugins_install', 'failed', ['results' => $results]);
            return $this->error(
                'One or more plugins failed to install.',
                422,
                'PLUGIN_INSTALL_PARTIAL_FAILED',
                ['results' => $results]
            );
        }

        $state->mergeState([
            'selected_plugins' => $selectedSlugs,
            'installed_plugins' => $results,
        ]);
        $state->completeStep(4);
        $this->logStep('plugins_install', 'passed', ['results' => $results]);

        return $this->success([
            'message' => 'Plugin installation completed.',
            'results' => $results,
        ]);
    }

    public function skipPlugins(InstallerStateService $state): JsonResponse
    {
        if (! $state->hasCompletedStep(3)) {
            $this->logStep('plugins_skip', 'failed', ['reason' => 'step_blocked']);
            return $this->error('Purpose selection must be completed before plugin step.', 422, 'INSTALLER_STEP_BLOCKED');
        }

        $this->logStep('plugins_skip', 'started');

        $state->mergeState([
            'selected_plugins' => [],
            'installed_plugins' => [],
        ]);
        $state->completeStep(4);

        $this->logStep('plugins_skip', 'passed');

        return $this->success([
            'message' => 'Plugin step skipped.',
        ]);
    }

    public function themes(InstallerStateService $state, MarketplaceCatalogService $catalog): JsonResponse
    {
        if (! $state->hasCompletedStep(4)) {
            $this->logStep('themes', 'failed', ['reason' => 'step_blocked']);
            return $this->error('Plugin installation must complete before theme listing.', 422, 'INSTALLER_STEP_BLOCKED');
        }

        $purpose = (string) Arr::get($state->state(), 'purpose.purpose', 'other');

        return $this->success($catalog->themes($purpose));
    }

    public function installThemes(
        InstallSelectionRequest $request,
        InstallerStateService $state,
        MarketplaceCatalogService $catalog,
        InstallerThemeInstallerService $themes
    ): JsonResponse {
        if (! $state->hasCompletedStep(4)) {
            $this->logStep('themes_install', 'failed', ['reason' => 'step_blocked']);
            return $this->error('Plugin installation must complete before theme install.', 422, 'INSTALLER_STEP_BLOCKED');
        }

        $this->logStep('themes_install', 'started');

        $selectedSlugs = array_values(array_unique(array_map('strval', (array) $request->validated('slugs'))));
        $selectedItems = $catalog->findThemePackagesBySlugs($selectedSlugs);

        if ($selectedItems === []) {
            $this->logStep('themes_install', 'failed', ['reason' => 'invalid_selection', 'slugs' => $selectedSlugs]);
            return $this->error('No valid theme selection found in catalog.', 422, 'THEME_SELECTION_INVALID');
        }

        $results = [];
        $failed = false;

        foreach ($selectedItems as $item) {
            $slug = (string) ($item['slug'] ?? '');
            $packageName = (string) ($item['package_name'] ?? '');

            $result = $themes->installComposerPackage($packageName);
            $ok = (bool) ($result['ok'] ?? false);

            $results[] = [
                'slug' => $slug,
                'package_name' => $packageName,
                'ok' => $ok,
                'message' => (string) ($result['message'] ?? ''),
            ];

            if (! $ok) {
                $failed = true;
            }
        }

        if ($failed) {
            $this->logStep('themes_install', 'failed', ['results' => $results]);
            return $this->error(
                'One or more themes failed to install.',
                422,
                'THEME_INSTALL_PARTIAL_FAILED',
                ['results' => $results]
            );
        }

        $state->mergeState([
            'selected_themes' => $selectedSlugs,
            'installed_themes' => $results,
        ]);
        $state->completeStep(5);
        $this->logStep('themes_install', 'passed', ['results' => $results]);

        return $this->success([
            'message' => 'Theme installation completed.',
            'results' => $results,
        ]);
    }

    public function skipThemes(InstallerStateService $state): JsonResponse
    {
        if (! $state->hasCompletedStep(4)) {
            $this->logStep('themes_skip', 'failed', ['reason' => 'step_blocked']);
            return $this->error('Plugin step must be completed before theme step.', 422, 'INSTALLER_STEP_BLOCKED');
        }

        $this->logStep('themes_skip', 'started');

        $state->mergeState([
            'selected_themes' => [],
            'installed_themes' => [],
        ]);
        $state->completeStep(5);

        $this->logStep('themes_skip', 'passed');

        return $this->success([
            'message' => 'Theme step skipped.',
        ]);
    }

    public function preflight(InstallerStateService $state, SystemCheckService $checks): JsonResponse
    {
        if (! $state->hasCompletedAll([1, 2, 3, 4, 5])) {
            $this->logStep('preflight', 'failed', ['reason' => 'step_blocked']);
            return $this->error('All previous steps must be completed before preflight.', 422, 'INSTALLER_STEP_BLOCKED');
        }

        $this->logStep('preflight', 'started');

        $checkResult = $checks->run();
        if (($checkResult['ok'] ?? false) !== true) {
            $this->logStep('preflight', 'failed', ['checks' => $checkResult]);
            return $this->error('Preflight failed due to unmet system requirements.', 422, 'INSTALLER_PREFLIGHT_FAILED', [
                'checks' => $checkResult,
            ]);
        }

        $this->logStep('preflight', 'passed');

        return $this->success([
            'ok' => true,
            'message' => 'Preflight checks passed.',
            'checks' => $checkResult,
        ]);
    }

    public function finalize(
        FinalizeInstallationRequest $request,
        InstallerStateService $state,
        FrontendThemeManagerService $themeManager
    ): JsonResponse {
        if (! $state->hasCompletedAll([1, 2, 3, 4, 5])) {
            $this->logStep('finalize', 'failed', ['reason' => 'step_blocked']);
            return $this->error('All previous steps must be completed before finalize.', 422, 'INSTALLER_STEP_BLOCKED');
        }

        $this->logStep('finalize', 'started');

        try {
            $payload = $request->validated();

            $result = $state->withLock(function () use ($state, $payload, $themeManager): array {
                Artisan::call('migrate', ['--force' => true]);
                Artisan::call('db:seed', ['--module' => 'core']);

                $currentState = $state->state();
                $configuration = (array) Arr::get($currentState, 'configuration', []);

                $siteData = [
                    'name' => (string) Arr::get($payload, 'site.name', 'Default Site'),
                    'slug' => (string) Arr::get($payload, 'site.slug', 'default'),
                    'domain' => (string) Arr::get($payload, 'site.domain', parse_url((string) Arr::get($configuration, 'app_url', URL::to('/')), PHP_URL_HOST) ?: 'localhost'),
                    'locale' => (string) Arr::get($payload, 'site.locale', 'en'),
                ];

                $site = Site::query()->firstOrCreate(
                    ['slug' => Str::slug($siteData['slug']) ?: 'default'],
                    [
                        'name' => $siteData['name'] !== '' ? $siteData['name'] : 'Default Site',
                        'domain' => $siteData['domain'] !== '' ? $siteData['domain'] : 'localhost',
                        'locale' => $siteData['locale'] !== '' ? $siteData['locale'] : 'en',
                        'is_active' => true,
                    ]
                );

                if (Admin::query()->count() === 0) {
                    $admin = (array) Arr::get($configuration, 'admin', []);

                    if ((string) ($admin['username'] ?? '') === '' || (string) ($admin['password'] ?? '') === '') {
                        throw ValidationException::withMessages([
                            'admin' => ['Admin credentials are missing from configuration step.'],
                        ]);
                    }

                    Admin::query()->create([
                        'name' => (string) ($admin['name'] ?? $admin['username']),
                        'username' => (string) $admin['username'],
                        'email' => (string) ($admin['email'] ?? null) ?: null,
                        'password' => bcrypt((string) $admin['password']),
                    ]);
                }

                $themes = array_values(array_filter(array_map(
                    static fn (array $item): ?string => ($item['ok'] ?? false) ? (string) ($item['slug'] ?? '') : null,
                    (array) Arr::get($currentState, 'installed_themes', [])
                )));

                if ($themes !== []) {
                    $themeManager->activate($themes[0], (int) $site->id);
                }

                Artisan::call('optimize:clear');

                $state->markInstalled();

                return [
                    'installed' => true,
                    'site_id' => (int) $site->id,
                    'redirect_to' => route('core.admin.login'),
                ];
            });
        } catch (ValidationException $e) {
            $this->logStep('finalize', 'failed', ['reason' => 'validation', 'errors' => $e->errors()]);
            return $this->error('Finalize validation failed.', 422, 'INSTALLER_FINALIZE_VALIDATION_FAILED', [
                'messages' => $e->errors(),
            ]);
        } catch (Throwable $e) {
            $this->logStep('finalize', 'failed', ['reason' => $e->getMessage()]);
            return $this->error('Finalize failed: '.$e->getMessage(), 500, 'INSTALLER_FINALIZE_FAILED');
        }

        $this->logStep('finalize', 'passed', ['result' => $result]);

        return $this->success($result);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logStep(string $step, string $status, array $context = []): void
    {
        Log::info('installer.step', [
            'step' => $step,
            'status' => $status,
            ...$context,
        ]);
    }
}
