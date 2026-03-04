<?php

return [
    'modules' => [
        'core' => [
            'enabled' => true,
            'name' => 'Core',
            'slug' => 'core',
            'description' => 'System backbone module',
            'ui_contract_path' => __DIR__ . '/../ui-contract.json',
            'menu' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'core.admin.dashboard',
                    'permission' => 'core.site.view',
                    'group' => 'Core',
                    'order' => 10,
                ],
                [
                    'label' => 'Audit logs',
                    'route' => 'core.admin.audit.index',
                    'permission' => 'core.audit.view',
                    'group' => 'Core',
                    'order' => 20,
                ],
                [
                    'label' => 'API tokens',
                    'route' => 'core.admin.tokens.index',
                    'permission' => 'core.token.manage',
                    'group' => 'Core',
                    'order' => 30,
                ],
                [
                    'label' => 'Modules',
                    'route' => 'core.admin.modules.index',
                    'permission' => 'core.module.manage',
                    'group' => 'Core',
                    'order' => 40,
                ],
            ],
        ],
        'content' => [
            'enabled' => true,
            'name' => 'Content',
            'slug' => 'content',
            'description' => 'Content modeling and entry management module',
            'ui_contract_path' => __DIR__ . '/../../content/ui-contract.json',
            'menu' => [
                [
                    'label' => 'Content',
                    'route' => 'content.admin.dashboard',
                    'permission' => 'content.type.viewAny',
                    'group' => 'Content',
                    'order' => 100,
                ],
            ],
        ],
    ],

    'admin_ui' => [
        'themes_base_path' => env('ADMIN_THEMES_BASE_PATH', 'themes/admin'),
        'theme' => env('ADMIN_THEME', 'default'),
        'fallback_theme' => env('ADMIN_THEME_FALLBACK', 'default'),
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
