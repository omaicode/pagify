<?php

namespace Modules\Core\Services;

class HookRegistry
{
    /**
     * @var array<string, array<int, callable>>
     */
    private array $listeners = [];

    public function register(string $hook, callable $listener): void
    {
        $this->listeners[$hook] ??= [];
        $this->listeners[$hook][] = $listener;
    }

    /**
     * @return array<int, mixed>
     */
    public function dispatch(string $hook, mixed $payload = null): array
    {
        $results = [];

        foreach ($this->listeners[$hook] ?? [] as $listener) {
            $results[] = $listener($payload, $hook);
        }

        return $results;
    }

    public function has(string $hook): bool
    {
        return ! empty($this->listeners[$hook] ?? []);
    }
}
