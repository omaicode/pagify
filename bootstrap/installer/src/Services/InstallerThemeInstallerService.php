<?php

namespace Pagify\Installer\Services;

use Illuminate\Support\Facades\File;
use Pagify\Core\Services\FrontendThemeManifestService;
use Pagify\Updater\Services\ComposerCommandRunner;

class InstallerThemeInstallerService
{
    public function __construct(
        private readonly ComposerCommandRunner $composer,
        private readonly FrontendThemeManifestService $manifestService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function installComposerPackage(string $packageName): array
    {
        if (! preg_match('/^[a-z0-9_.-]+\/[a-z0-9_.-]+$/i', $packageName)) {
            return [
                'ok' => false,
                'message' => 'Invalid package name format. Use vendor/package.',
            ];
        }

        $result = $this->composer->run(['require', $packageName]);

        if (! $result['ok']) {
            return [
                'ok' => false,
                'message' => trim((string) ($result['error_output'] ?? 'Composer require failed for theme package.')),
            ];
        }

        $manifestPath = $this->findManifestPath($packageName);

        if ($manifestPath === null) {
            return [
                'ok' => false,
                'message' => 'Theme package installed but theme.json was not found.',
            ];
        }

        $parsed = $this->manifestService->readManifestFile($manifestPath);
        $manifest = $parsed['manifest'];

        if (! is_array($manifest) || $parsed['errors'] !== []) {
            return [
                'ok' => false,
                'message' => 'Invalid theme manifest in package.',
                'errors' => $parsed['errors'],
            ];
        }

        $slug = (string) ($manifest['slug'] ?? '');

        if ($slug === '') {
            return [
                'ok' => false,
                'message' => 'Theme manifest is missing slug.',
            ];
        }

        $sourceRoot = dirname($manifestPath);
        $themesBasePath = base_path(trim((string) config('core.frontend_ui.themes_base_path', 'themes/main'), '/'));
        $targetRoot = $themesBasePath.'/'.$slug;

        File::ensureDirectoryExists($themesBasePath);

        if (is_dir($targetRoot)) {
            File::deleteDirectory($targetRoot);
        }

        File::copyDirectory($sourceRoot, $targetRoot);

        return [
            'ok' => true,
            'message' => 'Theme installed from composer package.',
            'theme' => [
                'slug' => $slug,
                'name' => (string) ($manifest['name'] ?? ucfirst($slug)),
                'version' => (string) ($manifest['version'] ?? ''),
                'package_name' => $packageName,
                'root_path' => $targetRoot,
            ],
        ];
    }

    private function findManifestPath(string $packageName): ?string
    {
        $vendorRoot = base_path('vendor/'.$packageName);

        $candidates = [
            $vendorRoot.'/theme.json',
            $vendorRoot.'/resources/theme.json',
            $vendorRoot.'/theme/theme.json',
            $vendorRoot.'/dist/theme.json',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
