<?php

return [
    [
        'key' => 'media.library',
        'label' => 'Media',
        'label_key' => 'media_nav_library',
        'route' => 'media.admin.library.index',
        'group' => 'Content',
        'order' => 35,
        'permission' => 'media.asset.viewAny',
        'active_patterns' => [
            '/admin/media',
            '/admin/media/library',
        ],
    ],
];
