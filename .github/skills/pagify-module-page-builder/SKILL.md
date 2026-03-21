---
name: pagify-module-page-builder
description: 'Phát triển tính năng, sửa lỗi, refactor và review cho module page-builder của Pagify (Laravel 12 + Inertia Vue + Webstudio). Dùng khi làm page CRUD, editor SPA shell, compatibility APIs, asset proxy, publish state và lifecycle trang.'
argument-hint: 'Mục tiêu thay đổi ở page-builder module là gì? (feature, bugfix, refactor, security, test)'
user-invocable: true
---

# Pagify Page Builder Development Workflow

## Mục Tiêu

Skill này chuẩn hóa quy trình làm việc với module page-builder để:
- Triển khai tính năng mới cho page CRUD và editor Webstudio
- Sửa lỗi không gây hồi quy ở lifecycle biên tập/truy xuất/trạng thái publish
- Refactor an toàn nhưng vẫn giữ nguyên API contract
- Đảm bảo dữ liệu page-scoped đúng theo mô hình `projectId = pageId`
- Đảm bảo test coverage và tiêu chí hoàn tất

## Khi Nào Sử Dụng

Sử dụng skill này khi thay đổi trong phạm vi `modules/page-builder` và các điểm tích hợp liên quan:
- Page CRUD trong admin interface
- Embedded editor SPA shell và Webstudio bootstrap
- Compatibility APIs (`/data`, `/patch`, `trpc`, resources loader)
- Asset upload/delete và proxy `/cgi/image/*`, `/cgi/asset/*`
- Publish/draft state theo từng trang

## Inputs Cần Có

- Loại thay đổi: `feature`, `bugfix`, `refactor`, `security`, `test-only`
- Impact scope: API, admin UI, editor bootstrap, page state persistence, assets, publish
- Mục tiêu nghiệp vụ và expected behavior
- Ràng buộc tương thích ngược (nếu có)

## Quy Trình Thực Thi

1. Xác định phạm vi và rủi ro
- Xác định route/API/service/repository bị ảnh hưởng.
- Đánh dấu `high` risk nếu thay đổi `/data`, `/patch`, mapping `projectId`, publish state, hoặc asset proxy.
- Liệt kê behavior contract cần giữ nguyên (payload shape, optimistic versioning, page-scoped persistence).

2. Kiểm tra tài liệu và code map
- Đọc docs page-builder trước, sau đó map tới code thực tế trong `modules/page-builder`.
- Xác định đúng điểm sửa: route, controller, service, repository, request, resource, policy, UI shell.
- Không đặt business logic trong controller.

3. Thiết kế thay đổi theo kiến trúc
- Đưa nghiệp vụ vào `src/Services` theo SRP.
- Tách truy cập dữ liệu vào `src/Repositories`.
- Dùng dependency injection; tránh facade trừ `config`, `log`, `auth`.
- Dùng typed properties và return types theo PHP 8.2 + PSR-12.

4. Nhánh quyết định theo loại thay đổi
- Nếu là `feature`:
  - Định nghĩa input/output contract và validation trước.
  - Làm rõ ảnh hưởng đến editor lifecycle: bootstrap -> edit -> patch -> publish.
  - Xác nhận dữ liệu mới thuộc persisted snapshot hay live server data.
- Nếu là `bugfix`:
  - Tái tạo bug bằng test (ưu tiên) hoặc ghi rõ bước tái tạo.
  - Sửa tối thiểu để xử lý root cause, tránh mở rộng phạm vi.
- Nếu là `security`:
  - Kiểm tra access-token/verify-token, phân quyền API và upload/delete asset.
  - Đánh giá nguy cơ lộ dữ liệu qua compatibility endpoints hoặc proxy endpoints.
- Nếu là `refactor`:
  - Giữ nguyên API contract và behavior, bổ sung regression tests.

5. Luồng quan trọng cần bảo toàn
- Laravel phục vụ editor SPA shell tại `/{admin_prefix}/page-builder/editor-spa/{path?}` ổn định.
- `projectId` của compatibility layer luôn map đúng `pageId` hiện tại.
- `GET /data/{projectId}` trả persisted UI state + metadata live từ database.
- `POST /patch` chỉ lưu snapshot state cần thiết cho editor reconstruction.
- Trạng thái publish phải luôn hydrate theo page đang chọn từ dữ liệu live.
- Upload/delete assets tương thích Webstudio và đồng bộ media system.

6. Admin UI và API integration (nếu có)
- Admin routes tham chiếu:
  - `/{admin_prefix}/page-builder/pages`
  - `/{admin_prefix}/page-builder/pages/{page}/preview`
  - `/{admin_prefix}/page-builder/editor-spa/{path?}`
- Primary API groups tham chiếu:
  - `api/v1/{admin_prefix}/page-builder/registry`
  - `api/v1/{admin_prefix}/page-builder/editor/access-token`
  - `api/v1/{admin_prefix}/page-builder/editor/verify-token`
  - `api/v1/{admin_prefix}/page-builder/editor/contract`
  - `api/v1/{admin_prefix}/page-builder/pages`
  - `api/v1/{admin_prefix}/page-builder/pages/{page}`
  - `api/v1/{admin_prefix}/page-builder/pages/{page}/publish`
- Compatibility APIs tham chiếu:
  - `GET api/v1/{admin_prefix}/page-builder/data/{projectId}`
  - `POST api/v1/{admin_prefix}/page-builder/patch`
  - `POST api/v1/{admin_prefix}/page-builder/resources-loader`
  - `GET|POST api/v1/{admin_prefix}/page-builder/assets`
  - `POST api/v1/{admin_prefix}/page-builder/assets/{name}`
  - `DELETE api/v1/{admin_prefix}/page-builder/assets/{assetId}`
  - `GET|POST api/v1/{admin_prefix}/page-builder/trpc/{path?}`
  - `POST api/v1/{admin_prefix}/page-builder/dashboard-logout`
- Asset proxy endpoints:
  - `GET /cgi/image/{path?}`
  - `GET /cgi/asset/{path?}`

7. Test và xác minh
- Thêm/cập nhật tests trong `modules/page-builder/tests/Feature` và `modules/page-builder/tests/Unit`.
- Ưu tiên test cho controller/service/policy mới hoặc bị sửa.
- Xác minh các nhóm test chính từ docs:
  - page builder lifecycle
  - embedded Webstudio shell bootstrap
  - page-scoped compatibility data
  - asset upload và delete compatibility
  - publish state hydration từ live database state

8. Hoàn tất và báo cáo
- Tóm tắt thay đổi: nguyên nhân, giải pháp, phạm vi tác động.
- Liệt kê rủi ro còn lại và phương án rollback/mitigation.
- Cập nhật docs/config nếu thay đổi contract hoặc hành vi vận hành.

## Tiêu Chí Hoàn Tất

- Kiến trúc đúng convention module và SRP service/repository
- Không đưa business logic vào controller
- Dữ liệu page-scoped và mapping `projectId = pageId` luôn đúng
- Snapshot persistence không ghi đè dữ liệu động đáng ra phải lấy từ server live
- Test mới đã có và test liên quan pass
- Không phá vỡ route/API contract hiện có (trừ khi được yêu cầu)

## Tài Liệu Tham Chiếu

- Page Builder module docs: `docs/docs/modules/page-builder-module.md`
- Workspace conventions: `.github/instructions/copilot.instructions.md`

## Mẫu Prompt Gọi Skill

- `/pagify-module-page-builder Sửa lỗi patch lưu sai page state khi chuyển trang, tạo test tái tạo bug trước khi fix.`
- `/pagify-module-page-builder Thêm endpoint compatibility để trả metadata trang theo dữ liệu live, không lấy từ snapshot.`
- `/pagify-module-page-builder Refactor service xử lý /data và /patch để giữ đúng page-scoped persistence, giữ nguyên API contract.`
