<?php

namespace Pagify\Installer\Services;

use Illuminate\Foundation\Application;

class SystemCheckService
{
    /**
     * @return array<string, mixed>
     */
    public function run(): array
    {
        $requirements = (array) config('installer.requirements', []);

        $checks = [
            'php_version' => $this->checkPhpVersion((string) ($requirements['php_min'] ?? '8.2.0')),
            'laravel_version' => $this->checkLaravelVersion((string) ($requirements['laravel_min'] ?? '12.0.0')),
            'extensions' => $this->checkExtensions((array) ($requirements['required_extensions'] ?? [])),
            'permissions' => $this->checkWritablePaths((array) ($requirements['required_writable_paths'] ?? [])),
            'upload_limit' => $this->checkUploadLimit((int) ($requirements['min_upload_bytes'] ?? 8 * 1024 * 1024)),
            'post_max_size' => $this->checkPostMaxSize((int) ($requirements['min_post_bytes'] ?? 8 * 1024 * 1024)),
        ];

        $allPassed = true;
        foreach ($checks as $item) {
            if (($item['ok'] ?? false) !== true) {
                $allPassed = false;
                break;
            }
        }

        return [
            'ok' => $allPassed,
            'checks' => $checks,
        ];
    }

    private function checkPhpVersion(string $minVersion): array
    {
        $current = PHP_VERSION;
        $ok = version_compare($current, $minVersion, '>=');

        return [
            'ok' => $ok,
            'severity' => $ok ? 'info' : 'blocker',
            'required' => $minVersion,
            'current' => $current,
            'message' => $ok
                ? trans('installer::ui.system_checks.messages.php_version_ok')
                : trans('installer::ui.system_checks.messages.php_version_fail'),
            'reason' => $ok
                ? trans('installer::ui.system_checks.messages.php_version_reason_ok', [
                    'current' => $current,
                ])
                : trans('installer::ui.system_checks.messages.php_version_reason_fail', [
                    'current' => $current,
                    'required' => $minVersion,
                ]),
        ];
    }

    private function checkLaravelVersion(string $minVersion): array
    {
        $current = Application::VERSION;
        $ok = version_compare($current, $minVersion, '>=');

        return [
            'ok' => $ok,
            'severity' => $ok ? 'info' : 'blocker',
            'required' => $minVersion,
            'current' => $current,
            'message' => $ok
                ? trans('installer::ui.system_checks.messages.laravel_version_ok')
                : trans('installer::ui.system_checks.messages.laravel_version_fail'),
            'reason' => $ok
                ? trans('installer::ui.system_checks.messages.laravel_version_reason_ok', [
                    'current' => $current,
                ])
                : trans('installer::ui.system_checks.messages.laravel_version_reason_fail', [
                    'current' => $current,
                    'required' => $minVersion,
                ]),
        ];
    }

    /**
     * @param array<int, string> $extensions
     */
    private function checkExtensions(array $extensions): array
    {
        $missing = [];

        foreach ($extensions as $extension) {
            $normalized = trim((string) $extension);
            if ($normalized === '') {
                continue;
            }

            if (! extension_loaded($normalized)) {
                $missing[] = $normalized;
            }
        }

        $ok = $missing === [];

        return [
            'ok' => $ok,
            'severity' => $ok ? 'info' : 'blocker',
            'required' => array_values($extensions),
            'missing' => $missing,
            'message' => $ok
                ? trans('installer::ui.system_checks.messages.extensions_ok')
                : trans('installer::ui.system_checks.messages.extensions_fail'),
            'reason' => $ok
                ? trans('installer::ui.system_checks.messages.extensions_reason_ok')
                : trans('installer::ui.system_checks.messages.extensions_reason_fail', [
                    'items' => implode(', ', $missing),
                ]),
        ];
    }

    /**
     * @param array<int, string> $paths
     */
    private function checkWritablePaths(array $paths): array
    {
        $missingPaths = [];
        $unwritablePaths = [];

        foreach ($paths as $relativePath) {
            $normalized = trim((string) $relativePath, '/');
            if ($normalized === '') {
                continue;
            }

            $fullPath = base_path($normalized);
            if (! is_dir($fullPath)) {
                $missingPaths[] = $normalized;
                continue;
            }

            if (! is_writable($fullPath)) {
                $unwritablePaths[] = $normalized;
            }
        }

        $ok = $missingPaths === [] && $unwritablePaths === [];

        $reason = trans('installer::ui.system_checks.messages.permissions_reason_ok');
        if ($missingPaths !== [] && $unwritablePaths !== []) {
            $reason = trans('installer::ui.system_checks.messages.permissions_reason_fail_both', [
                'missing' => implode(', ', $missingPaths),
                'unwritable' => implode(', ', $unwritablePaths),
            ]);
        } elseif ($missingPaths !== []) {
            $reason = trans('installer::ui.system_checks.messages.permissions_reason_fail_missing', [
                'missing' => implode(', ', $missingPaths),
            ]);
        } elseif ($unwritablePaths !== []) {
            $reason = trans('installer::ui.system_checks.messages.permissions_reason_fail_unwritable', [
                'unwritable' => implode(', ', $unwritablePaths),
            ]);
        }

        return [
            'ok' => $ok,
            'severity' => $ok ? 'info' : 'blocker',
            'required' => array_values($paths),
            'missing_paths' => $missingPaths,
            'unwritable_paths' => $unwritablePaths,
            'message' => $ok
                ? trans('installer::ui.system_checks.messages.permissions_ok')
                : trans('installer::ui.system_checks.messages.permissions_fail'),
            'reason' => $reason,
        ];
    }

    private function checkUploadLimit(int $requiredBytes): array
    {
        $currentBytes = $this->toBytes((string) ini_get('upload_max_filesize'));
        $ok = $currentBytes >= $requiredBytes;

        return [
            'ok' => $ok,
            'severity' => $ok ? 'info' : 'warning',
            'required_bytes' => $requiredBytes,
            'current_bytes' => $currentBytes,
            'message' => $ok
                ? trans('installer::ui.system_checks.messages.upload_limit_ok')
                : trans('installer::ui.system_checks.messages.upload_limit_fail'),
            'reason' => $ok
                ? trans('installer::ui.system_checks.messages.upload_limit_reason_ok', [
                    'current' => $this->formatBytes($currentBytes),
                ])
                : trans('installer::ui.system_checks.messages.upload_limit_reason_fail', [
                    'current' => $this->formatBytes($currentBytes),
                    'required' => $this->formatBytes($requiredBytes),
                ]),
        ];
    }

    private function checkPostMaxSize(int $requiredBytes): array
    {
        $currentBytes = $this->toBytes((string) ini_get('post_max_size'));
        $ok = $currentBytes >= $requiredBytes;

        return [
            'ok' => $ok,
            'severity' => $ok ? 'info' : 'warning',
            'required_bytes' => $requiredBytes,
            'current_bytes' => $currentBytes,
            'message' => $ok
                ? trans('installer::ui.system_checks.messages.post_max_size_ok')
                : trans('installer::ui.system_checks.messages.post_max_size_fail'),
            'reason' => $ok
                ? trans('installer::ui.system_checks.messages.post_max_size_reason_ok', [
                    'current' => $this->formatBytes($currentBytes),
                ])
                : trans('installer::ui.system_checks.messages.post_max_size_reason_fail', [
                    'current' => $this->formatBytes($currentBytes),
                    'required' => $this->formatBytes($requiredBytes),
                ]),
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return number_format($bytes / (1024 * 1024 * 1024), 1).' GB';
        }

        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 1).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return (string) $bytes.' B';
    }

    private function toBytes(string $value): int
    {
        $trimmed = strtolower(trim($value));
        if ($trimmed === '') {
            return 0;
        }

        $unit = substr($trimmed, -1);
        $number = (float) $trimmed;

        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }
}
