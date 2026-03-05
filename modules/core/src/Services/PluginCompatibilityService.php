<?php

namespace Pagify\Core\Services;

class PluginCompatibilityService
{
    /**
     * @param array<string, mixed> $manifest
     * @return array{compatible: bool, issues: array<int, string>}
     */
    public function evaluate(array $manifest): array
    {
        $issues = [];
        $requires = $manifest['requires'] ?? [];

        if (! is_array($requires)) {
            return [
                'compatible' => true,
                'issues' => [],
            ];
        }

        $phpConstraint = $requires['php'] ?? null;

        if (is_string($phpConstraint) && $phpConstraint !== '' && ! $this->matches(PHP_VERSION, $phpConstraint)) {
            $issues[] = sprintf('PHP %s does not satisfy %s.', PHP_VERSION, $phpConstraint);
        }

        $laravelConstraint = $requires['laravel'] ?? null;
        $laravelVersion = app()->version();

        if (is_string($laravelConstraint) && $laravelConstraint !== '' && ! $this->matches($laravelVersion, $laravelConstraint)) {
            $issues[] = sprintf('Laravel %s does not satisfy %s.', $laravelVersion, $laravelConstraint);
        }

        $coreConstraint = $requires['core'] ?? null;
        $coreVersion = $this->resolveCoreVersion();

        if (is_string($coreConstraint) && $coreConstraint !== '' && $coreVersion !== null && ! $this->matches($coreVersion, $coreConstraint)) {
            $issues[] = sprintf('Pagify Core %s does not satisfy %s.', $coreVersion, $coreConstraint);
        }

        return [
            'compatible' => $issues === [],
            'issues' => $issues,
        ];
    }

    private function resolveCoreVersion(): ?string
    {
        $packageName = (string) config('plugins.compatibility.core_package', 'omaicode/pagify-core');

        if ($packageName === '') {
            return null;
        }

        $lockPath = base_path('composer.lock');

        if (! is_file($lockPath)) {
            return null;
        }

        $raw = file_get_contents($lockPath);

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return null;
        }

        foreach (['packages', 'packages-dev'] as $group) {
            $items = $decoded[$group] ?? [];

            if (! is_array($items)) {
                continue;
            }

            foreach ($items as $package) {
                if (! is_array($package)) {
                    continue;
                }

                $name = $package['name'] ?? null;
                $version = $package['version'] ?? null;

                if ($name === $packageName && is_string($version)) {
                    return $version;
                }
            }
        }

        return null;
    }

    private function matches(string $version, string $constraint): bool
    {
        $orSets = array_map('trim', explode('||', $constraint));

        foreach ($orSets as $orSet) {
            if ($orSet === '') {
                continue;
            }

            $parts = preg_split('/\s*,\s*|\s+/', $orSet) ?: [];
            $allMatch = true;

            foreach ($parts as $part) {
                $part = trim((string) $part);

                if ($part === '' || $part === '*') {
                    continue;
                }

                if (! $this->matchesToken($version, $part)) {
                    $allMatch = false;
                    break;
                }
            }

            if ($allMatch) {
                return true;
            }
        }

        return false;
    }

    private function matchesToken(string $version, string $token): bool
    {
        if (str_starts_with($token, '^')) {
            $base = ltrim($token, '^');
            $segments = explode('.', $this->normalize($base));
            $major = (int) ($segments[0] ?? 0);
            $upper = ($major + 1).'.0.0';

            return version_compare($version, $base, '>=') && version_compare($version, $upper, '<');
        }

        if (str_starts_with($token, '~')) {
            $base = ltrim($token, '~');
            $segments = explode('.', $this->normalize($base));
            $major = (int) ($segments[0] ?? 0);
            $minor = (int) ($segments[1] ?? 0);
            $upper = sprintf('%d.%d.0', $major, $minor + 1);

            return version_compare($version, $base, '>=') && version_compare($version, $upper, '<');
        }

        foreach (['>=', '<=', '>', '<', '='] as $operator) {
            if (str_starts_with($token, $operator)) {
                $target = trim(substr($token, strlen($operator)));

                return version_compare($version, $target, $operator);
            }
        }

        return version_compare($version, $token, '==');
    }

    private function normalize(string $version): string
    {
        return preg_replace('/[^0-9.]/', '', $version) ?: $version;
    }
}
