<?php

namespace Pagify\Core\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Pagify\Core\Models\ModuleState;
use Throwable;

class ModuleStateService
{
    private const CACHE_KEY = 'core:module-states:v1';

    public function __construct(private readonly CacheRepository $cache)
    {
    }

    /**
     * @return array<string, bool>
     */
    public function states(): array
    {
        return $this->cache->rememberForever(self::CACHE_KEY, function (): array {
            try {
                return ModuleState::query()
                    ->get(['module', 'enabled'])
                    ->mapWithKeys(static fn (ModuleState $state): array => [
                        (string) $state->module => (bool) $state->enabled,
                    ])
                    ->all();
            } catch (Throwable) {
                return [];
            }
        });
    }

    public function setEnabled(string $module, bool $enabled): void
    {
        ModuleState::query()->updateOrCreate(
            ['module' => $module],
            ['enabled' => $enabled]
        );

        $this->clearCache();
    }

    public function clearCache(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }
}
