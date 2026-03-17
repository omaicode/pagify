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
    Pagify\Installer\Providers\InstallerServiceProvider::class,
    Pagify\Core\Providers\CoreServiceProvider::class,
    Pagify\Updater\Providers\UpdaterServiceProvider::class,
    Pagify\Media\Providers\MediaServiceProvider::class,
    Pagify\PageBuilder\Providers\PageBuilderServiceProvider::class,
];
