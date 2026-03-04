<?php

return [
    'field_types' => [
        'text',
        'richtext',
        'number',
        'date',
        'boolean',
        'select',
        'media',
        'relation',
        'repeater',
        'conditional',
    ],

    'relation_types' => [
        'hasOne',
        'hasMany',
        'manyToMany',
    ],

    'permissions' => [
        'content.type.viewAny',
        'content.type.create',
        'content.type.update',
        'content.type.delete',
        'content.entry.viewAny',
        'content.entry.view',
        'content.entry.create',
        'content.entry.update',
        'content.entry.delete',
        'content.entry.publish',
        'content.entry.revision.view',
        'content.entry.revision.rollback',
        'content.api.read',
    ],
];
