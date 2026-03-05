<?php

namespace Pagify\Core\Contracts;

use Pagify\Core\Services\EventBus;

interface CoreHookSubscriber
{
    public function register(EventBus $eventBus): void;
}
