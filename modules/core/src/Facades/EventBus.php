<?php

namespace Modules\Core\Facades;

use Illuminate\Support\Facades\Facade;

class EventBus extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'core.event-bus';
    }
}
