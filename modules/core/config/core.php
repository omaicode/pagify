<?php

return [
    'modules' => [
        'core' => [
            'enabled' => true,
            'name' => 'Core',
            'slug' => 'core',
            'config_key' => 'core',
            'description' => 'System backbone module',
            'ui_contract_path' => __DIR__ . '/../ui-contract.json',
        ],
        'content' => [
            'enabled' => true,
            'name' => 'Content',
            'slug' => 'content',
            'config_key' => 'content',
            'description' => 'Content modeling and entry management module',
            'ui_contract_path' => __DIR__ . '/../../content/ui-contract.json',
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
