---
name: pagify-module-core
description: 'Phát triển tính năng, sửa lỗi, refactor và review cho module core của Pagify (Laravel 12 + Inertia Vue). ùng khi làm auth admin, role-permission, admin groups, admins, tokens, plugin/theme management, audit logs, frontend fallback, và governance.'
argument-hint: 'Mục tiêu thay đổi ở core module là gì? (feature, bugfix, refactor, security, test)'
user-invocable: true
---

# Pagify Core Development Workflow

## Mục Tiêu

Skill này chuẩn hóa quy trình làm việc với module core để:
- Triển khai feature mới đúng kiến trúc module
- Sửa lỗi không gây hồi quy
- Refactor an toàn và dễ bảo trì
- Đảm bảo test coverage và tiêu chí hoàn tất

## Khi Nào Sử Dụng

Sử dụng skill này khi thay đổi trong phạm vi `modules/core` và các điểm tích hợp liên quan:
- Admin auth, session, forgot/reset password
- Dashboard và settings pages (admin)
- Permission, admin groups, admins, API tokens
- Plugin/theme management endpoints
- Audit logs, access governance
- Frontend fallback routing, theme asset serving

## Inputs Cần Có

- Loại thay đổi: `feature`, `bugfix`, `refactor`, `security`, `test-only`
- Impact scope: API, admin UI, auth/permission, plugin/theme, fallback routing
- Mục tiêu nghiệp vụ và expected behavior
- Ràng buộc backward compatibility (nếu có)

## Quy Trình Thực Thi

1. Xác định phạm vi và rủi ro
- Xác định route/API/chức năng bị ảnh hưởng trong core.
- Đánh dấu mức độ rủi ro: `high` nếu đóng vào auth, permission, token, middleware, fallback routing.
- Liệt kê behavioral contract cần giữ nguyên.

2. Kiểm tra tài liệu và code map
- Đọc tài liệu module core trước, sau đó map tới code thực tế trong `modules/core`.
- Xác định đúng điểm sửa: route, controller, service, repository, policy, request, resource.
- Không đặt business logic trong controller.

3. Thiết kế thay đổi theo kiến trúc
- Đưa logic nghiệp vụ vào `src/Services` theo SRP.
- Tách logic truy cập dữ liệu vào `src/Repositories`.
- Dùng dependency injection; tránh facade trừ `config`, `log`, `auth`.
- Đặt typed properties và return types theo PHP 8.2 + PSR-12.

4. Nhánh quyết định theo loại thay đổi
- Nếu là `feature`:
  - Định nghĩa API contract và validation trước.
  - Thêm service/repository trước controller.
  - Cập nhật policy/permission nếu cần.
- Nếu là `bugfix`:
  - Tái tạo bug bằng test (ưu tiên) hoặc ghi rõ steps tái tạo.
  - Sửa tối thiểu để giải quyết root cause, tránh scope creep.
- Nếu là `security`:
  - Kiểm tra authz/authn, token scope, input validation, audit log.
  - Đánh giá tác động denial paths và escalation paths.
- Nếu là `refactor`:
  - Giữ nguyên public behavior, bổ sung regression tests.

5. Admin UI và public integration (nếu có)
- Nếu có admin page:
  - Theo convention Inertia + Vue 3 + Tailwind trong `themes/admin/{theme_name}`.
  - Dùng Composition API, `<script setup lang="ts">`, typed props/emits.
  - Form sử dụng Inertia form helper + server-side validation qua FormRequest.
- Nếu ảnh hưởng public site:
  - Xử lý data ở backend, truyền dữ liệu đã xử lý sang Twig templates.

6. Test và xác minh
- Thêm hoặc cập nhật tests trong `modules/core/tests/Feature` và `modules/core/tests/Unit`.
- Ưu tiên test cho controller/service/policy mới hoặc bị sửa.
- Chạy test liên quan theo scope thay đổi.
- Xác minh các luồng quan trọng của core:
  - access management
  - admin end-to-end flow
  - plugin/theme management behavior
  - profile flow
  - frontend fallback va theme sandbox hardening

7. Hoàn tất và báo cáo
- Viết tóm tắt thay đổi: nguyên nhân, giải pháp, phạm vi ảnh hưởng.
- Liệt kê rủi ro còn lại và cách rollback (nếu cần).
- Đảm bảo migration/config docs được cập nhật khi có thay đổi liên quan.

## Tiêu Chí Hoàn Tất

- Kiến trúc đúng convention module + SRP service/repository
- Không đưa business logic vào controller
- Validation/authz/policy được cập nhật đầy đủ theo scope
- Test mới đã có và test liên quan pass
- Không phá vỡ route/API contract hiện có (trừ khi được yêu cầu)
- Có ghi chú rủi ro và hướng verify sau khi merge

## Tài Liệu Tham Khảo

- Core module docs: `docs/docs/modules/core-module.md`
- Workspace conventions: `.github/instructions/copilot.instructions.md`

## Mẫu Prompt Gọi Skill

- `/pagify-core Sửa lỗi 403 sai permission khi cập nhật admin group, tạo test tái tạo bug trước khi fix.`
- `/pagify-core Thêm endpoint quản lý API token cho admin, gồm service/repository, policy và feature tests.`
- `/pagify-core Refactor plugin management controller để đưa business logic vào service, giữ nguyên API contract.`
