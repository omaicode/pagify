<?php

return [
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
];
