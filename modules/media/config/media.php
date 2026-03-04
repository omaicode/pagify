<?php

return [
    'settings_keys' => [
        'disk' => 'media.storage.disk',
        'base_path' => 'media.storage.base_path',
    ],

    'storage' => [
        'default_disk' => 'public',
        'base_path' => 'media',
        'allowed_disks' => ['public', 's3'],
    ],

    'uploads' => [
        'max_file_size_bytes' => 1024 * 1024 * 512,
        'chunk_size_bytes' => 1024 * 1024 * 10,
    ],

    'image_transforms' => [
        'generate_thumbnail_on_upload' => true,
        'thumbnail_width' => 480,
        'thumbnail_height' => 320,
        'queue' => 'default',
    ],

    'permissions' => [
        'media.asset.viewAny',
        'media.asset.view',
        'media.asset.create',
        'media.asset.update',
        'media.asset.delete',
        'media.folder.manage',
        'media.tag.manage',
    ],
];
