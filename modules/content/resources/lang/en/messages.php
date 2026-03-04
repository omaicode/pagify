<?php

return [
    'dashboard' => [
        'title' => 'Content module',
        'description' => 'Manage content types, entries, and publishing workflows from shared admin shell.',
    ],

    'api' => [
        'relation_field_not_found' => 'Relation field not found.',
        'content_entry_not_found' => 'Content entry not found.',
    ],

    'status' => [
        'content_type_created' => 'Content type created. Migration plan queued #:plan_id.',
        'content_type_updated' => 'Content type updated. Migration plan queued #:plan_id.',
        'content_type_deleted' => 'Content type deleted successfully.',
        'content_entry_created' => 'Content entry created successfully.',
        'content_entry_updated' => 'Content entry updated successfully.',
        'content_entry_deleted' => 'Content entry deleted successfully.',
        'content_entry_published' => 'Content entry published successfully.',
        'content_entry_moved_to_draft' => 'Content entry moved to draft successfully.',
        'content_schedule_updated' => 'Content schedule updated successfully.',
        'schema_builder_saved' => 'Schema builder saved. Migration plan queued #:plan_id.',
        'migration_retry_queued' => 'Migration plan #:plan_id queued for retry.',
        'entry_rollback_completed' => 'Entry rollback completed.',
    ],
];
