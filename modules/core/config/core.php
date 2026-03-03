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
                [
                    'label' => 'API tokens',
                    'route' => 'core.admin.tokens.index',
                    'permission' => 'core.token.manage',
                ],
                [
                    'label' => 'Modules',
                    'route' => 'core.admin.modules.index',
                    'permission' => 'core.module.manage',
                ],
            ],
        ],
    ],

    'locales' => [
        'default' => 'en',
        'supported' => ['en', 'vi'],
    ],

    'hook_subscribers' => [
        // Example: Modules\Blog\Hooks\BlogHookSubscriber::class,
    ],
];
