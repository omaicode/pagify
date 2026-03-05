<?php

namespace Tests\Unit;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Pagify\Core\Contracts\CoreHookSubscriber;
use Pagify\Core\Events\EntryCreated;
use Pagify\Core\Services\EventBus;
use Pagify\Core\Services\HookRegistry;
use PHPUnit\Framework\TestCase;

class EventBusTest extends TestCase
{
    public function test_emit_hook_runs_registered_listener(): void
    {
        $eventBus = $this->makeEventBus();

        $eventBus->onHook('entry.created', static fn (array $payload): string => 'ok:' . $payload['id']);

        $result = $eventBus->emitHook('entry.created', ['id' => 42]);

        $this->assertSame(['ok:42'], $result);
    }

    public function test_bridge_event_to_hook_dispatches_payload(): void
    {
        $eventBus = $this->makeEventBus();
        $capturedEntryId = null;

        $eventBus->bridgeEventToHook(EntryCreated::class, 'entry.created');
        $eventBus->onHook('entry.created', static function (EntryCreated $event) use (&$capturedEntryId): void {
            $capturedEntryId = (string) $event->entryId;
        });

        $eventBus->publish(new EntryCreated('post', 55));

        $this->assertSame('55', $capturedEntryId);
    }

    public function test_register_subscriber_contract_registers_hooks(): void
    {
        $eventBus = $this->makeEventBus();

        $subscriber = new class implements CoreHookSubscriber {
            public function register(EventBus $eventBus): void
            {
                $eventBus->onHook('custom.hook', static fn (): string => 'subscribed');
            }
        };

        $eventBus->registerSubscriber($subscriber);
        $result = $eventBus->emitHook('custom.hook');

        $this->assertSame(['subscribed'], $result);
    }

    private function makeEventBus(): EventBus
    {
        $dispatcher = new Dispatcher(new Container());

        return new EventBus($dispatcher, new HookRegistry());
    }
}
