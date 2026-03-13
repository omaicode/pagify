<?php

namespace Pagify\Installer\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Throwable;

class InstallerStateService
{
    public function isInstalled(): bool
    {
        if (is_file($this->installedFilePath())) {
            return true;
        }

        return (bool) Arr::get($this->state(), 'installed', false);
    }

    /**
     * @return array<string, mixed>
     */
    public function state(): array
    {
        $defaults = [
            'installed' => false,
            'current_step' => 1,
            'completed_steps' => [],
            'configuration' => [],
            'purpose' => null,
            'selected_plugins' => [],
            'selected_themes' => [],
            'installed_plugins' => [],
            'installed_themes' => [],
            'updated_at' => now()->toIso8601String(),
        ];

        $file = $this->stateFilePath();

        if (! is_file($file)) {
            return $defaults;
        }

        $decoded = json_decode((string) file_get_contents($file), true);

        if (! is_array($decoded)) {
            return $defaults;
        }

        return array_replace_recursive($defaults, $decoded);
    }

    /**
     * @param array<string, mixed> $state
     */
    public function putState(array $state): void
    {
        $state['updated_at'] = now()->toIso8601String();

        File::ensureDirectoryExists(dirname($this->stateFilePath()));
        File::put($this->stateFilePath(), json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
    }

    /**
     * @param array<string, mixed> $patch
     */
    public function mergeState(array $patch): array
    {
        $state = array_replace_recursive($this->state(), $patch);
        $this->putState($state);

        return $state;
    }

    public function hasCompletedStep(int $step): bool
    {
        $completed = array_map('intval', (array) Arr::get($this->state(), 'completed_steps', []));

        return in_array($step, $completed, true);
    }

    public function completeStep(int $step): array
    {
        $state = $this->state();
        $completed = array_map('intval', (array) Arr::get($state, 'completed_steps', []));

        if (! in_array($step, $completed, true)) {
            $completed[] = $step;
            sort($completed);
        }

        $state['completed_steps'] = $completed;
        $state['current_step'] = max((int) Arr::get($state, 'current_step', 1), $step + 1);

        $this->putState($state);

        return $state;
    }

    /**
     * @param array<int, int> $steps
     */
    public function hasCompletedAll(array $steps): bool
    {
        foreach ($steps as $step) {
            if (! $this->hasCompletedStep((int) $step)) {
                return false;
            }
        }

        return true;
    }

    public function markInstalled(): void
    {
        $state = $this->state();
        $state['installed'] = true;
        $state['current_step'] = 6;

        $completed = array_map('intval', (array) Arr::get($state, 'completed_steps', []));
        if (! in_array(6, $completed, true)) {
            $completed[] = 6;
            sort($completed);
        }
        $state['completed_steps'] = $completed;

        $this->putState($state);

        File::ensureDirectoryExists(dirname($this->installedFilePath()));
        File::put($this->installedFilePath(), now()->toIso8601String()."\n");
    }

    /**
     * @template TReturn
     * @param callable(): TReturn $callback
     * @return TReturn
     */
    public function withLock(callable $callback)
    {
        $path = $this->lockFilePath();
        File::ensureDirectoryExists(dirname($path));

        $handle = fopen($path, 'c+');

        if (! is_resource($handle)) {
            throw new \RuntimeException('Unable to acquire installer lock file.');
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                throw new \RuntimeException('Unable to acquire installer lock.');
            }

            return $callback();
        } finally {
            try {
                flock($handle, LOCK_UN);
            } catch (Throwable) {
            }

            fclose($handle);
        }
    }

    private function stateFilePath(): string
    {
        return (string) config('installer.state.state_file', storage_path('app/installer/state.json'));
    }

    private function installedFilePath(): string
    {
        return (string) config('installer.state.installed_file', storage_path('app/installer/installed.lock'));
    }

    private function lockFilePath(): string
    {
        return (string) config('installer.state.lock_file', storage_path('app/installer/install.lock'));
    }
}
