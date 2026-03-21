---
name: pagify-module-updater
description: 'Phát triển tính năng, sửa lỗi, refactor và review cho module updater của Pagify (Laravel 12 + Inertia Vue). ùng khi làm updater executions, dry-run, update module/all, rollback, output sanitization, và updater admin APIs.'
argument-hint: 'Mục tiêu thay đổi ở updater module là gì? (feature, bugfix, refactor, security, test)'
user-invocable: true
---

# Pagify Updater Development Workflow

## Mục Tiêu

Skill này chuẩn hóa quy trình làm việc với module updater để:
- Triển khai feature mới an toàn cho luồng cập nhật
- Sửa lỗi không gây hồi quy ở updater APIs và admin page
- Refactor mà vẫn giữ nguyên hành vi public
- Đảm bảo output hiển thị an toàn (sanitized)
- Đảm bảo test coverage và tiêu chí hoàn tất

## Khi Nào Sử Dụng

Sử dụng skill này khi thay đổi trong phạm vi `modules/updater` và các điểm tích hợp liên quan:
- Execution history listing và execution details
- Dry-run update workflows
- Update 1 module hoặc update tất cả mapped modules
- Rollback update executions
- Output sanitization cho UI display
- Updater admin page hoặc updater admin APIs

## Inputs Cần Có

- Loại thay đổi: `feature`, `bugfix`, `refactor`, `security`, `test-only`
- Impact scope: API, admin UI, execution pipeline, sanitization, rollback
- Mục tiêu nghiệp vụ và expected behavior
- Ràng buộc compatibility và rollback expectation

## Quy Trình Thực Thi

1. Xác định phạm vi và rủi ro
- Xác định endpoint, route, command, service nào bị ảnh hưởng.
- Đánh dấu `high` risk nếu đóng vào rollback, execution state transition, hoặc output sanitization.
- Liệt kê các behavior contract phải giữ nguyên.

2. Kiểm tra tài liệu và code map
- Đọc updater docs trước, sau đó map tới code trong `modules/updater`.
- Xác định đúng điểm sửa: route, controller, service, repository, request, resource, policy.
- Không đặt business logic trong controller.

3. Thiết kế thay đổi theo kiến trúc
- Đưa nghiệp vụ vào `src/Services` theo SRP.
- Ách truy cập dữ liệu vào `src/Repositories` nếu cần.
- Dùng dependency injection; tránh facade trừ `config`, `log`, `auth`.
- Dùng typed properties và return types theo PHP 8.2 + PSR-12.

4. Nhánh quyết định theo loại thay đổi
- Nếu là `feature`:
  - Định nghĩa input/output contract và validation trước.
  - Làm rõ pre-checks cho dry-run và update execution.
  - Đảm bảo rollback path có thể thực hiện được.
- Nếu là `bugfix`:
  - Tái tạo bug bằng test (ưu tiên) hoặc ghi rõ reproduction steps.
  - Sửa tối thiểu để giải quyết root cause, tránh mở rộng scope.
- Nếu là `security`:
  - Kiểm tra output sanitization, authorization và input validation.
  - Đánh giá nguy cơ leak thông tin nhạy cảm từ updater output.
- Nếu là `refactor`:
  - Giữ nguyên API contract và behavior, bổ sung regression tests.

5. Luồng quan trọng cần bảo toàn
- Execution history listing và detail retrieval dùng schema dữ liệu.
- Dry-run không được thay đổi state thực tế của hệ thống.
- Update module/all ghi nhận execution đúng và có kết quả rõ ràng.
- Rollback đúng execution và không gây state drift.
- Output response cho UI được sanitize đúng kỳ vọng.

6. Admin UI và API integration (nếu có)
- Admin route tham chiếu: `/{admin_prefix}/updater`.
- API groups tham chiếu:
  - `api/v1/{admin_prefix}/updater/executions`
  - `api/v1/{admin_prefix}/updater/executions/dry-run`
  - `api/v1/{admin_prefix}/updater/executions/module/{module}`
  - `api/v1/{admin_prefix}/updater/executions/all`
  - `api/v1/{admin_prefix}/updater/executions/{execution}/rollback`

7. Test và xác minh
- Thêm/cập nhật tests trong `modules/updater/tests/Feature` và `modules/updater/tests/Unit`.
- Ưu tiên test cho controller/service/policy mới hoặc bị sửa.
- Xác minh các nhóm test trong docs:
  - updater API behavior
  - updater page rendering
  - updater output sanitization
- Nếu thay đổi command flow, test thêm command scenarios:
  - `updater:module`
  - `updater:all`
  - `updater:rollback`

8. Hoàn tất và báo cáo
- Tóm tắt thay đổi: nguyên nhân, giải pháp, phạm vi tác động.
- Liệt kê rủi ro còn lại và rollback/mitigation plan.
- Cập nhật docs/config nếu có thay đổi contract hoặc vận hành.

## Tiêu Chí Hoàn Tất

- Kiến trúc đúng convention module và SRP service/repository
- Không đưa business logic vào controller
- Validation, authorization và sanitization được cập nhật đầy đủ
- Test mới đã có và test liên quan pass
- Không phá vỡ route/API contract hiện có (trừ khi được yêu cầu)
- Có hướng verify an toàn cho dry-run, update, rollback, và UI output

## Tài Liệu Tham Chiếu

- Updater module docs: `docs/docs/modules/updater-module.md`
- Workspace conventions: `.github/instructions/copilot.instructions.md`

## Mẫu Prompt Gọi Skill

- `/pagify-module-updater Sua loi rollback execution bi sai state transition, tao test tai tao bug truoc khi fix.`
- `/pagify-module-updater Them endpoint de hien thi execution details day du va sanitize output cho admin UI.`
- `/pagify-module-updater Refactor updater execution service de tach ro dry-run va update run chinh, giu nguyen API contract.`
