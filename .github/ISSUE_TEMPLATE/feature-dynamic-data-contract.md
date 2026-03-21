---
name: Feature: Dynamic Data Contract for Page Builder
about: Định nghĩa, expose và preview dynamic data_contract cho component động trong module/plugin Page Builder
labels: ["type:feature", "module:page-builder", "area:api", "area:admin-ui", "priority:high"]
---

## Summary
Thêm khả năng định nghĩa và expose dynamic `data_contract` (id, schema, sample) cho component động trong module/plugin. API trả về contract, frontend preview bằng sample, chuẩn bị cho engine render JSON contract.

## Context / Problem
Hiện tại chưa có chuẩn contract dữ liệu cho component động, gây khó khăn cho việc preview và mở rộng engine render.

## Scope
- In-scope: Định nghĩa, expose, preview sample contract động.
- Out-of-scope: Engine render thực tế, block tĩnh legacy.

## Todo List
- [ ] Thiết kế cấu trúc `data_contract` (id, schema, sample)
- [ ] Cho phép module/plugin đăng ký contract động
- [ ] Service quản lý contract
- [ ] Expose API contract động
- [ ] Thêm sample data cho preview
- [ ] Frontend nhận contract, preview sample
- [ ] Test service, API, frontend
- [ ] Tài liệu hướng dẫn

## Acceptance Criteria
- Định nghĩa contract động cho component
- API trả về contract đúng format
- Frontend preview đúng sample
- Có test đầy đủ
- Có tài liệu hướng dẫn

## Risks / Notes
- Backward compatibility
- Chuẩn hóa schema (nên dùng JSON Schema)
- Kiểm soát quyền truy cập API
