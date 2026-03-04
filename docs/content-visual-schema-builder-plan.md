# Content Visual Schema Builder plan & checklist

Last updated: 2026-03-04

## Mục tiêu

Xây dựng giao diện Visual Schema Builder cho module `content` theo định hướng:

- Drag-drop đầy đủ (field palette + kéo thả sắp xếp).
- No-code 100% (không YAML/JSON/code trong UI).
- Save sẽ trigger queue để **thực thi migration DDL** (không chỉ tạo plan).
- Đồng bộ cho cả `Builder` và trang `create/edit content type`.

## Phạm vi

- Backend: request validation, schema normalize, queue job, migration plan lifecycle, DDL executor.
- Frontend: builder UI mới, tái sử dụng cho create/edit, status theo vòng đời queue.
- Test: feature + regression + permission + idempotency + failure/retry.
- Docs: cập nhật runbook cho luồng queue/migration.

## Kế hoạch triển khai theo pha

### Phase 1 — Chuẩn hóa contract schema + backend foundation

Mục tiêu: gom mọi luồng ghi schema về cùng contract no-code để chuẩn bị execution.

- Chuẩn hóa payload field schema (type/config/validation/conditional/relation/repeater).
- Đồng bộ rules giữa:
  - `SaveSchemaBuilderRequest`
  - `StoreContentTypeRequest`
  - `UpdateContentTypeRequest`
- Giữ normalize trung tâm ở `ContentTypeService`.
- Bổ sung trạng thái/lifecycle cho `ContentSchemaMigrationPlan` (queued/planning/executing/applied/failed/retryable).
- Thêm metadata cần thiết cho idempotency và retry.

### Phase 2 — Queue thực thi migration DDL thật

Mục tiêu: chuyển từ “plan-only” sang “plan + execute”.

- Tách service executor DDL chuyên dụng (an toàn, có guard idempotent).
- Cập nhật `QueueSchemaMigrationPlanJob` để chạy execution thật.
- Bật dispatch sau commit để tránh race condition transaction.
- Ràng buộc retry/backoff và lock tránh xử lý trùng plan.
- Ghi rõ log/audit khi apply hoặc fail.

### Phase 3 — UI Visual Builder drag-drop full + no-code 100%

Mục tiêu: thay toàn bộ nhập JSON tay bằng visual form controls.

- Tạo field palette để kéo loại field vào canvas.
- Kéo thả reorder field trong canvas.
- Inspector no-code theo từng field type:
  - text/richtext/number/date/boolean/select/media/relation/repeater/conditional
- Xóa JSON textarea khỏi builder/create/edit.
- Tái sử dụng cùng component cho:
  - Builder edit
  - Content type create
  - Content type edit

### Phase 4 — Status, vận hành và hardening

Mục tiêu: vận hành ổn định và quan sát được.

- Cập nhật status page hiển thị lifecycle execution + lỗi + retry action.
- Cập nhật `docs/runbook.md` cho queue worker, retry, rollback guideline.
- Hoàn thiện acceptance tests và regression tests.

## Checklist thực thi

### [A] Architecture & data contract
- [x] Chốt schema contract no-code cho toàn bộ field types.
- [x] Đồng bộ validation rules ở 3 request classes.
- [x] Chuẩn hóa normalize flow ở `ContentTypeService`.
- [x] Loại bỏ các đường ghi schema trùng logic.

### [B] Queue DDL execution
- [x] Thêm migration mở rộng bảng `content_schema_migration_plans` cho execution metadata.
- [x] Tạo DDL executor service với idempotency guard.
- [x] Nâng `QueueSchemaMigrationPlanJob` để gọi executor.
- [x] Áp dụng `afterCommit` cho dispatch.
- [x] Cấu hình retry/backoff/lock phù hợp.
- [x] Ghi log lỗi có context để debug production.

### [C] Visual drag-drop UI
- [x] Tạo field palette component.
- [x] Tạo canvas drag-drop component.
- [x] Tạo inspector controls cho từng field type.
- [x] Bỏ hoàn toàn JSON textarea.
- [x] Đồng bộ UI cho Builder + Create + Edit.
- [x] Đảm bảo tương thích dữ liệu cũ (nếu schema đã tồn tại).

### [D] Status & operations
- [x] Hiển thị trạng thái execution chi tiết trên status page.
- [x] Hỗ trợ retry khi plan ở trạng thái failed/retryable.
- [x] Cập nhật runbook cho queue worker và quy trình xử lý lỗi.

### [E] Tests
- [x] Mở rộng `ContentSchemaBuilderTest` cho save -> queue -> execute.
- [x] Thêm test idempotency (re-run cùng plan là no-op an toàn).
- [x] Thêm test failure path + retry.
- [x] Mở rộng `ContentTypeCrudTest` cho flow create/edit mới.
- [x] Mở rộng `ContentPermissionDeniedMatrixTest` cho endpoint mới.
- [x] Mở rộng `ContentMultiSiteIsolationHardeningTest` cho ràng buộc multi-site.
- [x] Chạy full `php artisan test` xanh.

## Tiêu chí hoàn thành (Definition of Done)

- Người dùng tạo/sửa schema hoàn toàn bằng drag-drop + form controls, không cần YAML/JSON/code.
- Save từ builder/create/edit đều queue và **thực thi DDL** thành công.
- Luồng execution có trạng thái rõ ràng, lỗi minh bạch, retry an toàn.
- Không có regression ở các module liên quan, toàn bộ test pass.
- Runbook đủ để vận hành queue migration trong môi trường local/staging.
