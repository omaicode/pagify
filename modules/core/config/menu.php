<?php

return [
    [
        'label' => 'Dashboard',
        'label_key' => 'dashboard',
        'route' => 'core.admin.dashboard',
        'permission' => 'core.site.view',
        'group' => 'Core',
        'order' => 10,
    ],
    [
        'label' => 'Settings',
        'label_key' => 'settings',
        'route' => 'core.admin.settings.index',
        'permission' => null,
        'group' => 'Core',
        'order' => 900,
        'active_patterns' => [
            '/admin/modules',
            '/admin/api-tokens',
            '/admin/audit-logs',
            '/admin/content',
            '/admin/updater'
        ],
    ],
];
