<?php

return [
    'queue' => [
        'connection' => env('UPDATER_QUEUE_CONNECTION'),
        'name' => env('UPDATER_QUEUE', 'default'),
    ],

    'composer' => [
        'bin' => env('UPDATER_COMPOSER_BIN', 'composer'),
        'timeout_seconds' => (int) env('UPDATER_COMPOSER_TIMEOUT', 900),
        'working_dir' => base_path(),
        'extra_args' => [
            '--no-interaction',
            '--no-progress',
        ],
    ],

    'marketplace' => [
        'enabled' => (bool) env('UPDATER_MARKETPLACE_ENABLED', false),
    ],

    'module_package_map' => [
        'core' => 'omaicode/pagify-core',
        'media' => 'omaicode/pagify-media',
        'updater' => 'omaicode/updater',
    ],

    'rollback' => [
        'snapshot_disk' => env('UPDATER_SNAPSHOT_DISK', 'local'),
        'snapshot_directory' => env('UPDATER_SNAPSHOT_DIR', 'updater/snapshots'),
    ],
];
