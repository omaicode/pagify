# Pagify Feature Engineer

Skill này giúp AI xử lý công việc kỹ thuật trong Pagify gồm: phát triển
tính năng mới, bảo trì, bảo dưỡng, hardening, và fix bug theo đúng kiến trúc
Laravel modular hiện tại, không phá các guardrails quan trọng như permission,
audit, multi-site isolation, và queue safety.

## Khi nào dùng

- Muốn thêm feature vào `core`, `content`, `media`, `updater`
- Muốn thêm API/admin UI/migration/test theo chuẩn dự án
- Muốn phân tích dự án trước khi estimate hoặc before coding
- Muốn bảo trì định kỳ, refactor an toàn, tối ưu độ ổn định
- Muốn debug/sửa lỗi production hoặc lỗi hồi quy (regression)

## Cấu trúc skill package

- `SKILL.md`: hành vi chính của skill
- `resources/project-profile.md`: mô tả kiến trúc Pagify từ codebase hiện tại
- `resources/feature-delivery-playbook.md`: checklist triển khai feature
- `examples/feature-spec-template.md`: mẫu yêu cầu feature để bắt đầu nhanh

## Cách dùng nhanh (step-by-step)

1. Mô tả feature theo template trong `examples/feature-spec-template.md`.
2. Yêu cầu AI chạy phân tích impact theo module + layers (DB/API/UI/Queue/Test).
3. Yêu cầu AI đưa kế hoạch implementation theo từng commit nhỏ.
4. Cho AI thực thi từng bước và chạy test liên quan sau mỗi bước.
5. Chốt bằng handoff gồm: thay đổi, lý do thiết kế, lệnh verify, risk/follow-up.

Khi là bugfix, ưu tiên flow:

1. Reproduce lỗi bằng test hoặc các bước chạy cụ thể.
2. Chốt root cause theo đúng layer gây lỗi.
3. Sửa tối thiểu + thêm regression test.
4. Đánh giá rủi ro deploy và đề xuất monitor.

## Best practices

- Bắt đầu từ test gần nhất với thay đổi để phản hồi nhanh.
- Giữ controller mỏng, đẩy logic vào service/action.
- Luôn xác định rõ permission matrix và site boundary ngay từ đầu.
- Ưu tiên mở rộng patterns có sẵn thay vì tạo framework mới trong nội bộ.

## Pitfalls cần tránh

- Chỉ sửa UI mà quên policy/audit ở backend.
- Thêm endpoint mới nhưng không thống nhất envelope/error format.
- Chạy tác vụ nặng ngay trong HTTP request thay vì queue/job.
- Viết test quá rộng ngay từ đầu làm chậm vòng lặp fix.
- Sửa triệu chứng bề mặt nhưng không khóa lỗi bằng regression test.
