---
name: pagify-module-media
description: 'Phát triển tính năng, sửa lỗi, refactor và review cho module media của Pagify (Laravel 12 + Inertia Vue). Dùng khi làm media library, folders, assets, upload sessions, chunked upload và media admin APIs.'
argument-hint: 'Mục tiêu thay đổi ở media module là gì? (feature, bugfix, refactor, security, test)'
user-invocable: true
---

# Pagify Media Development Workflow

## Mục Tiêu

Skill này chuẩn hóa quy trình làm việc với module media để:
- Triển khai tính năng mới đúng kiến trúc module
- Sửa lỗi không gây hồi quy luồng upload và quản lý tài nguyên
- Refactor an toàn nhưng vẫn giữ nguyên hành vi public
- Đảm bảo xử lý upload session/chunked upload ổn định
- Đảm bảo test coverage và tiêu chí hoàn tất

## Khi Nào Sử Dụng

Sử dụng skill này khi thay đổi trong phạm vi `modules/media` và các điểm tích hợp liên quan:
- Media library browsing
- Folder listing và folder creation
- Asset upload, metadata update, preview, download
- Upload sessions cho file lớn (chunked upload)
- Media admin page hoặc media admin APIs

## Inputs Cần Có

- Loại thay đổi: `feature`, `bugfix`, `refactor`, `security`, `test-only`
- Impact scope: API, admin UI, upload sessions, chunk handling, metadata
- Mục tiêu nghiệp vụ và expected behavior
- Ràng buộc tương thích ngược (nếu có)

## Quy Trình Thực Thi

1. Xác định phạm vi và rủi ro
- Xác định endpoint, route, service, repository bị ảnh hưởng.
- Đánh dấu `high` risk nếu thay đổi upload session lifecycle, chunk assembly, hoặc quyền truy cập asset.
- Liệt kê behavior contract cần giữ nguyên (response schema, trạng thái upload, quyền truy cập).

2. Kiểm tra tài liệu và code map
- Đọc media docs trước, sau đó map tới code trong `modules/media`.
- Xác định đúng điểm sửa: route, controller, service, repository, request, resource, policy.
- Không đặt business logic trong controller.

3. Thiết kế thay đổi theo kiến trúc
- Đưa nghiệp vụ vào `src/Services` theo SRP.
- Tách truy cập dữ liệu vào `src/Repositories`.
- Dùng dependency injection; tránh facade trừ `config`, `log`, `auth`.
- Dùng typed properties và return types theo PHP 8.2 + PSR-12.

4. Nhánh quyết định theo loại thay đổi
- Nếu là `feature`:
  - Định nghĩa input/output contract và validation trước.
  - Làm rõ vòng đời upload session: tạo session -> upload chunks -> complete session -> asset khả dụng.
  - Đảm bảo metadata và folder hierarchy nhất quán.
- Nếu là `bugfix`:
  - Tái tạo bug bằng test (ưu tiên) hoặc ghi rõ bước tái tạo.
  - Sửa tối thiểu để giải quyết root cause, tránh mở rộng phạm vi.
- Nếu là `security`:
  - Kiểm tra authorization cho upload, download, preview và metadata update.
  - Kiểm tra kiểm soát loại file, kích thước và xử lý đầu vào.
- Nếu là `refactor`:
  - Giữ nguyên public behavior/API contract, bổ sung regression tests.

5. Luồng quan trọng cần bảo toàn
- Duyệt media library không mất dữ liệu và phân trang/truy xuất đúng.
- Tạo và liệt kê folder đúng quan hệ.
- Upload file thường và chunked upload hoạt động ổn định.
- Complete upload session tạo asset đúng trạng thái và có thể truy xuất.
- Preview/download giữ đúng quyền truy cập.

6. Admin UI và API integration (nếu có)
- Admin route tham chiếu: `/{admin_prefix}/media`.
- API groups tham chiếu:
  - `api/v1/{admin_prefix}/media/assets`
  - `api/v1/{admin_prefix}/media/folders`
  - `api/v1/{admin_prefix}/media/upload-sessions`
- Nếu có thay đổi UI admin, tuân thủ Inertia + Vue 3 + Tailwind theo convention của repo.

7. Test và xác minh
- Thêm/cập nhật tests trong `modules/media/tests/Feature` và `modules/media/tests/Unit`.
- Ưu tiên test cho controller/service/policy mới hoặc bị sửa.
- Xác minh các nhóm test chính từ docs:
  - media module bootstrap
  - media upload và lifecycle operations
- Với thay đổi upload sessions, bắt buộc có test cho các trạng thái: create, upload chunks, complete.

8. Hoàn tất và báo cáo
- Tóm tắt thay đổi: nguyên nhân, giải pháp, phạm vi tác động.
- Liệt kê rủi ro còn lại và phương án rollback/mitigation.
- Cập nhật docs/config nếu thay đổi contract hoặc hành vi vận hành.

## Tiêu Chí Hoàn Tất

- Kiến trúc đúng convention module và SRP service/repository
- Không đưa business logic vào controller
- Validation và authorization được cập nhật đầy đủ theo scope
- Test mới đã có và test liên quan pass
- Không phá vỡ route/API contract hiện có (trừ khi được yêu cầu)
- Có hướng verify rõ cho library, folder, upload session và asset lifecycle

## Tài Liệu Tham Chiếu

- Media module docs: `docs/docs/modules/media-module.md`
- Workspace conventions: `.github/instructions/copilot.instructions.md`

## Mẫu Prompt Gọi Skill

- `/pagify-module-media Sửa lỗi complete upload session không tạo asset khả dụng, tạo test tái tạo bug trước khi fix.`
- `/pagify-module-media Thêm API cập nhật metadata asset với validation và policy đầy đủ.`
- `/pagify-module-media Refactor media upload service để tách rõ chunk handling và finalize flow, giữ nguyên API contract.`
