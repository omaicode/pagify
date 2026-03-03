<?php

namespace Modules\Core\Contracts;

use Modules\Core\Services\EventBus;

interface CoreHookSubscriber
{
    public function register(EventBus $eventBus): void;
}
