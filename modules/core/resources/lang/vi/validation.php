<?php

return [
    'messages' => [
        'required' => 'Trường :attribute là bắt buộc.',
        'string' => 'Trường :attribute phải là chuỗi.',
        'array' => 'Trường :attribute phải là mảng.',
        'boolean' => 'Trường :attribute phải là đúng hoặc sai.',
        'date' => 'Trường :attribute không phải ngày hợp lệ.',
        'after' => 'Trường :attribute phải là ngày sau :date.',
        'min' => 'Trường :attribute phải có ít nhất :min ký tự.',
        'max' => 'Trường :attribute không được vượt quá :max ký tự.',
        'confirmed' => 'Xác nhận :attribute không khớp.',
        'current_password' => 'Mật khẩu hiện tại không đúng.',
        'image' => 'Trường :attribute phải là hình ảnh.',
        'mimes' => 'Trường :attribute phải có định dạng: :values.',
        'in' => 'Giá trị đã chọn cho :attribute không hợp lệ.',
    ],
    'attributes' => [
        'name' => 'tên token',
        'nickname' => 'biệt hiệu',
        'bio' => 'giới thiệu',
        'abilities' => 'quyền hạn',
        'abilities.*' => 'quyền',
        'expires_at' => 'ngày hết hạn',
        'enabled' => 'trạng thái bật',
        'locale' => 'ngôn ngữ',
        'current_password' => 'mật khẩu hiện tại',
        'new_password' => 'mật khẩu mới',
        'new_password_confirmation' => 'xác nhận mật khẩu mới',
        'avatar' => 'avatar',
    ],
];
