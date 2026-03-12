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
        'assets' => [
            'cache_busting' => [
                'strategy' => env('FRONTEND_THEME_ASSET_CACHE_BUSTING', 'mtime'),
                'hash_length' => env('FRONTEND_THEME_ASSET_HASH_LENGTH', 12),
            ],
        ],
        'render' => [
            'engine' => env('FRONTEND_THEME_ENGINE', 'twig'),
            'strict_variables' => env('FRONTEND_THEME_STRICT_VARIABLES', true),
            'cache' => [
                'enabled' => env('FRONTEND_THEME_CACHE_ENABLED', true),
                'path' => env('FRONTEND_THEME_CACHE_PATH', storage_path('framework/cache/twig')),
            ],
            'sandbox' => [
                'enabled' => env('FRONTEND_THEME_SANDBOX_ENABLED', true),
                'allowed_tags' => [
                    'if',
                    'for',
                    'set',
                    'block',
                    'extends',
                    'include',
                    'with',
                    'apply',
                ],
                'allowed_filters' => [
                    'default',
                    'escape',
                    'e',
                    'raw',
                    'upper',
                    'lower',
                    'title',
                    'capitalize',
                    'length',
                    'join',
                    'trim',
                    'replace',
                    'slice',
                    'nl2br',
                    'striptags',
                    'date',
                    'u',
                ],
                'allowed_functions' => [],
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
