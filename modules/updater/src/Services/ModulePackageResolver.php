<?php

namespace Pagify\Updater\Services;

use Pagify\Core\Services\ModuleRegistry;

class ModulePackageResolver
{
    public function __construct(
        private readonly ModuleRegistry $modules,
    ) {
    }

    public function resolvePackageBySlug(string $moduleSlug): ?string
    {
        $map = config('updater.module_package_map', []);

        if (is_array($map) && isset($map[$moduleSlug]) && is_string($map[$moduleSlug])) {
            return $map[$moduleSlug];
        }

        $requires = $this->rootComposerRequires();

        foreach ($requires as $package => $constraint) {
            if (! is_string($package)) {
                continue;
            }

            if (str_ends_with($package, '/'.$moduleSlug) || str_ends_with($package, '-'.$moduleSlug)) {
                return $package;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public function resolveTargetPackages(string $targetType, ?string $targetValue = null): array
    {
        if ($targetType === 'module' && is_string($targetValue) && $targetValue !== '') {
            $package = $this->resolvePackageBySlug($targetValue);

            return $package !== null ? [$targetValue => $package] : [];
        }

        $resolved = [];

        foreach (array_keys($this->modules->all()) as $moduleSlug) {
            $package = $this->resolvePackageBySlug((string) $moduleSlug);

            if ($package === null) {
                continue;
            }

            $resolved[(string) $moduleSlug] = $package;
        }

        return $resolved;
    }

    /**
     * @return array<string, string>
     */
    private function rootComposerRequires(): array
    {
        $composerPath = base_path('composer.json');

        if (! is_file($composerPath)) {
            return [];
        }

        $raw = file_get_contents($composerPath);

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded) || ! isset($decoded['require']) || ! is_array($decoded['require'])) {
            return [];
        }

        $requires = [];

        foreach ($decoded['require'] as $package => $constraint) {
            if (! is_string($package) || ! is_string($constraint)) {
                continue;
            }

            $requires[$package] = $constraint;
        }

        return $requires;
    }
}
