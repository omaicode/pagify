<?php

return [
    'state' => [
        'state_file' => storage_path('app/installer/state.json'),
        'installed_file' => storage_path('app/installer/installed.lock'),
        'lock_file' => storage_path('app/installer/install.lock'),
    ],

    'guard' => [
        'enabled' => true,
        'enable_in_testing' => false,
        'except' => [
            'install',
            'install/*',
            'api/v1/install/*',
            'up',
            'build/*',
            'theme-assets/*',
            '_debugbar/*',
        ],
    ],

    'requirements' => [
        'php_min' => '8.2.0',
        'laravel_min' => '12.0.0',
        'required_extensions' => [
            'bcmath',
            'ctype',
            'curl',
            'dom',
            'fileinfo',
            'json',
            'mbstring',
            'openssl',
            'pdo',
            'tokenizer',
            'xml',
        ],
        'required_writable_paths' => [
            'storage',
            'bootstrap/cache',
        ],
        'min_upload_bytes' => 8 * 1024 * 1024,
        'min_post_bytes' => 8 * 1024 * 1024,
    ],

    'purposes' => [
        'blog' => [
            'label' => 'Blog',
            'recommended_plugins' => [
                'content-seo',
                'comments',
            ],
            'recommended_themes' => [
                'unified-blog',
            ],
        ],
        'company' => [
            'label' => 'Website cong ty',
            'recommended_plugins' => [
                'content-seo',
                'analytics',
            ],
            'recommended_themes' => [
                'corporate-lite',
            ],
        ],
        'ecommerce' => [
            'label' => 'Ban hang',
            'recommended_plugins' => [
                'content-seo',
                'shop-core',
                'payment-gateway',
            ],
            'recommended_themes' => [
                'storefront',
            ],
        ],
        'other' => [
            'label' => 'Khac',
            'recommended_plugins' => [],
            'recommended_themes' => [],
        ],
    ],

    'marketplace' => [
        'plugins' => [
            [
                'slug' => 'content-seo',
                'name' => 'Content SEO',
                'package_name' => 'omaicode/pagify-plugin-content-seo',
                'version_constraint' => '^1.0',
            ],
            [
                'slug' => 'comments',
                'name' => 'Comments',
                'package_name' => 'omaicode/pagify-plugin-comments',
                'version_constraint' => '^1.0',
            ],
            [
                'slug' => 'analytics',
                'name' => 'Analytics',
                'package_name' => 'omaicode/pagify-plugin-analytics',
                'version_constraint' => '^1.0',
            ],
            [
                'slug' => 'shop-core',
                'name' => 'Shop Core',
                'package_name' => 'omaicode/pagify-plugin-shop-core',
                'version_constraint' => '^1.0',
            ],
            [
                'slug' => 'payment-gateway',
                'name' => 'Payment Gateway',
                'package_name' => 'omaicode/pagify-plugin-payment-gateway',
                'version_constraint' => '^1.0',
            ],
        ],
        'themes' => [
            [
                'slug' => 'unified-blog',
                'name' => 'Unified Blog',
                'package_name' => 'omaicode/pagify-theme-unified-blog',
                'version_constraint' => '^1.0',
            ],
            [
                'slug' => 'corporate-lite',
                'name' => 'Corporate Lite',
                'package_name' => 'omaicode/pagify-theme-corporate-lite',
                'version_constraint' => '^1.0',
            ],
            [
                'slug' => 'storefront',
                'name' => 'Storefront',
                'package_name' => 'omaicode/pagify-theme-storefront',
                'version_constraint' => '^1.0',
            ],
        ],
    ],
];
