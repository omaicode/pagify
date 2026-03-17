<?php

return [
    'theme' => env('CORE_MAIL_UI_THEME', env('ADMIN_THEME', 'default')),

    'brand' => [
        'name' => env('CORE_MAIL_UI_BRAND_NAME', env('APP_NAME', 'Pagify')),
        'logo_url' => env('CORE_MAIL_UI_LOGO_URL'),
        'logo_alt' => env('CORE_MAIL_UI_LOGO_ALT', env('APP_NAME', 'Pagify')),
    ],

    'themes' => [
        'default' => [
            'page_bg' => '#f3f4f6',
            'card_bg' => '#ffffff',
            'card_border' => '#e5e7eb',
            'header_bg' => '#f9fafb',
            'header_text' => '#111827',
            'body_text' => '#111827',
            'muted_text' => '#4b5563',
            'subtle_text' => '#6b7280',
            'button_bg' => '#1d4ed8',
            'button_text' => '#ffffff',
            'divider' => '#e5e7eb',
            'info_box_bg' => '#eff6ff',
            'info_box_border' => '#bfdbfe',
            'info_box_text' => '#1e3a8a',
        ],
        'minimal' => [
            'page_bg' => '#f8fafc',
            'card_bg' => '#ffffff',
            'card_border' => '#cbd5e1',
            'header_bg' => '#ffffff',
            'header_text' => '#0f172a',
            'body_text' => '#0f172a',
            'muted_text' => '#334155',
            'subtle_text' => '#64748b',
            'button_bg' => '#0f172a',
            'button_text' => '#ffffff',
            'divider' => '#cbd5e1',
            'info_box_bg' => '#f1f5f9',
            'info_box_border' => '#cbd5e1',
            'info_box_text' => '#334155',
        ],
    ],

    // Emergency runtime override, JSON object, for quick styling changes per environment.
    'override_json' => env('CORE_MAIL_UI_OVERRIDE_JSON'),
];
