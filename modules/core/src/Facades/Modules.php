<?php

namespace Pagify\Core\Facades;

use Illuminate\Support\Facades\Facade;

class Modules extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'core.modules';
    }
}
