<?php

namespace Modules\Core\Facades;

use Illuminate\Support\Facades\Facade;

class Settings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'core.settings';
    }
}
