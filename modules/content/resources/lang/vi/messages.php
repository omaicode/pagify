<?php

return [
    'dashboard' => [
        'title' => 'Module nội dung',
        'description' => 'Quản lý loại nội dung, bản ghi và quy trình xuất bản trong giao diện quản trị dùng chung.',
    ],

    'api' => [
        'relation_field_not_found' => 'Không tìm thấy trường quan hệ.',
        'content_entry_not_found' => 'Không tìm thấy bản ghi nội dung.',
    ],

    'status' => [
        'content_type_created' => 'Đã tạo loại nội dung. Kế hoạch migration #:plan_id đã được đưa vào hàng đợi.',
        'content_type_updated' => 'Đã cập nhật loại nội dung. Kế hoạch migration #:plan_id đã được đưa vào hàng đợi.',
        'content_type_deleted' => 'Đã xóa loại nội dung thành công.',
        'content_entry_created' => 'Đã tạo bản ghi nội dung thành công.',
        'content_entry_updated' => 'Đã cập nhật bản ghi nội dung thành công.',
        'content_entry_deleted' => 'Đã xóa bản ghi nội dung thành công.',
        'content_entry_published' => 'Đã xuất bản bản ghi nội dung thành công.',
        'content_entry_moved_to_draft' => 'Đã chuyển bản ghi nội dung về nháp thành công.',
        'content_schedule_updated' => 'Đã cập nhật lịch nội dung thành công.',
        'schema_builder_saved' => 'Đã lưu schema builder. Kế hoạch migration #:plan_id đã được đưa vào hàng đợi.',
        'migration_retry_queued' => 'Kế hoạch migration #:plan_id đã được đưa vào hàng đợi chạy lại.',
        'entry_rollback_completed' => 'Đã rollback bản ghi thành công.',
    ],
];
