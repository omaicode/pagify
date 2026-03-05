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
    Pagify\Updater\Providers\UpdaterServiceProvider::class,
    Pagify\Core\Providers\CoreServiceProvider::class,
    Pagify\Content\Providers\ContentServiceProvider::class,
    Pagify\Media\Providers\MediaServiceProvider::class,
];
