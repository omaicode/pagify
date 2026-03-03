<?php

return [
    'modules' => [
        'core' => [
            'enabled' => true,
            'name' => 'Core',
            'slug' => 'core',
            'description' => 'System backbone module',
            'menu' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'core.admin.dashboard',
                    'permission' => null,
                ],
                [
                    'label' => 'Audit logs',
                    'route' => 'core.admin.audit.index',
                    'permission' => 'core.audit.view',
                ],
            ],
        ],
    ],

    'locales' => [
        'default' => 'en',
        'supported' => ['en', 'vi'],
    ],
];
