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

    'sites' => [
        'resolution' => [
            'enable_header_fallback' => true,
            'header_domain' => 'X-Site-Domain',
            'header_slug' => 'X-Site-Slug',
            'default_site_slug' => 'default',
        ],
    ],

    'hook_subscribers' => [
        // Example: Modules\Blog\Hooks\BlogHookSubscriber::class,
    ],

    'audit' => [
        'audited_models' => [
            Modules\Core\Models\Site::class,
            Modules\Core\Models\Setting::class,
            Modules\Core\Models\Admin::class,
            Modules\Core\Models\ModuleState::class,
        ],
        'redact_keys' => [
            'password',
            'password_confirmation',
            'token',
            'secret',
            'authorization',
            'cookie',
            'api_key',
            'remember_token',
        ],
        'redaction_mask' => '[REDACTED]',
        'retention_days' => 180,
        'cleanup_chunk_size' => 1000,
    ],
];
