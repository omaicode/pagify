<?php

return [
    'admin_ui' => [
        'themes_base_path' => env('ADMIN_THEMES_BASE_PATH', 'themes/admin'),
        'theme' => env('ADMIN_THEME', 'default'),
        'fallback_theme' => env('ADMIN_THEME_FALLBACK', 'default'),
    ],

    'frontend_ui' => [
        'themes_base_path' => env('FRONTEND_THEMES_BASE_PATH', 'themes/main'),
        'theme' => env('FRONTEND_THEME', 'unified'),
        'fallback_theme' => env('FRONTEND_THEME_FALLBACK', 'unified'),
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
        // Example: Pagify\Blog\Hooks\BlogHookSubscriber::class,
    ],

    'audit' => [
        'audited_models' => [
            Pagify\Core\Models\Site::class,
            Pagify\Core\Models\Setting::class,
            Pagify\Core\Models\Admin::class,
            Pagify\Core\Models\ModuleState::class,
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
