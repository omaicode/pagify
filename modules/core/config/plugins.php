<?php

return [
    'directories' => [
        base_path('plugins'),
    ],

    'safe_mode' => [
        'enabled' => true,
    ],

    'compatibility' => [
        'core_package' => env('PAGIFY_CORE_PACKAGE', 'omaicode/pagify-core'),
    ],
];
