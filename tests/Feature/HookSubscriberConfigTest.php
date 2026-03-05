<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pagify\Core\Contracts\CoreHookSubscriber;
use Pagify\Core\Providers\CoreServiceProvider;
use Pagify\Core\Services\EventBus;
use Tests\TestCase;

class HookSubscriberConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_hook_subscriber_is_loaded_from_config_by_provider(): void
    {
        config()->set('core.hook_subscribers', [TestHookSubscriber::class]);

        $this->app->register(CoreServiceProvider::class, force: true);

        /** @var EventBus $eventBus */
        $eventBus = $this->app->make(EventBus::class);

        $result = $eventBus->emitHook('test.hook.config');

        $this->assertContains('loaded-from-config', $result);
    }
}

class TestHookSubscriber implements CoreHookSubscriber
{
    public function register(EventBus $eventBus): void
    {
        $eventBus->onHook('test.hook.config', static fn (): string => 'loaded-from-config');
    }
}
