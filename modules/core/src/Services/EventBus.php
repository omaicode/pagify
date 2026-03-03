<?php

namespace Modules\Core\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Core\Contracts\CoreHookSubscriber;

class EventBus
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly HookRegistry $hooks,
    ) {
    }

    public function publish(object|string $event, mixed $payload = []): mixed
    {
        return $this->dispatcher->dispatch($event, $payload);
    }

    public function subscribe(string $event, callable|array|string $listener): void
    {
        $this->dispatcher->listen($event, $listener);
    }

    public function onHook(string $hook, callable $listener): void
    {
        $this->hooks->register($hook, $listener);
    }

    /**
     * @return array<int, mixed>
     */
    public function emitHook(string $hook, mixed $payload = null): array
    {
        return $this->hooks->dispatch($hook, $payload);
    }

    public function bridgeEventToHook(string $eventClass, string $hook): void
    {
        $this->dispatcher->listen($eventClass, function (object $event) use ($hook): void {
            $this->hooks->dispatch($hook, $event);
        });
    }

    public function registerSubscriber(CoreHookSubscriber $subscriber): void
    {
        $subscriber->register($this);
    }
}
