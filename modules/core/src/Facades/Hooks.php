<?php

namespace Pagify\Core\Facades;

use Illuminate\Support\Facades\Facade;

class Hooks extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'core.hooks';
    }
}
