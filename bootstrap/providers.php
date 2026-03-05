<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auto-Loaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */
    Modules\Core\Providers\CoreServiceProvider::class,
    Modules\Content\Providers\ContentServiceProvider::class,
    Modules\Media\Providers\MediaServiceProvider::class,
];
