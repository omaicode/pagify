---
name: pagify-feature-engineer
description: "Phân tích và triển khai công việc kỹ thuật cho dự án Pagify (Laravel 12)."
metadata:
  owner: "pagify-team"
---

# Goal

Triển khai feature và xử lý bảo trì/fix bug cho Pagify đúng kiến trúc hiện tại,
an toàn khi production, test được, và có thể bàn giao thành các thay đổi code
rõ ràng theo từng bước.

# Instructions

## 1) Read project context before coding

1. Đọc nhanh project profile tại `resources/project-profile.md`.
2. Xác định feature thuộc module nào (`core`, `content`, `media`, `updater`),
   hay cần tạo module mới.
3. Tóm tắt lại phạm vi theo format: Trigger, Inputs, Outputs, Constraints,
   Affected layers (DB/API/UI/Queue/Test).

## 2) Define implementation plan (small, verifiable steps)

1. Tách việc theo thứ tự ưu tiên:
   - Với feature mới: Schema/migrations → Domain → HTTP → UI → Queue/Audit → Tests
   - Với fix bug/bảo trì: Reproduce bug → Root cause → Minimal fix → Regression tests
2. Mỗi bước phải có tiêu chí done rõ ràng và cách verify.

## 2.1) Maintenance & Bugfix mode

1. Luôn bắt đầu bằng tái hiện lỗi (test case, request payload, hoặc log trace).
2. Xác định root cause theo layer (data, domain, HTTP contract, UI state, queue).
3. Ưu tiên sửa nhỏ nhất để chặn lỗi, không mở rộng scope ngoài yêu cầu.
4. Bổ sung regression test để khóa lỗi không tái diễn.
5. Nếu có rủi ro production, đề xuất rollback/feature flag/tạm mitigation.

## 3) Enforce Pagify architecture contracts

1. Dùng module boundaries, không đẩy business logic vào controller.
2. API phải thống nhất envelope và permission checks.
3. Mọi thao tác mutation phải đi qua audit + policy/permission tương ứng.
4. Luôn áp dụng multi-site isolation cho dữ liệu có ownership theo site.
5. Với flow async, dùng queue/job; không chạy tác vụ nặng trong request.

## 4) Implement feature with defensive quality checks

1. Viết code thay đổi tối thiểu nhưng giải quyết root cause.
2. Nếu thêm endpoint:
   - FormRequest cho validate
   - Resource/DTO response nhất quán
   - Middleware stack phù hợp module
3. Nếu thêm UI admin:
   - Có loading/empty/error/success/403 states
   - Tôn trọng i18n và site context
4. Nếu thêm persistence:
   - Migration có index/constraint hợp lý
   - Có kế hoạch rollback hoặc retry-safe nếu dùng queue.

## 5) Verify in increasing confidence

1. Chạy test gần nhất với phần vừa đổi trước.
2. Mở rộng ra nhóm test feature liên quan.
3. Cuối cùng chạy scope rộng hơn nếu cần (không sửa lỗi ngoài phạm vi nếu không liên quan).

## 6) Deliver structured handoff

1. Báo cáo:
   - What changed
   - Why this design fits Pagify
   - How to run/verify
   - Risks + follow-up
2. Nếu yêu cầu user mơ hồ, hỏi 1-3 câu chốt phạm vi trước khi code.

# Examples

## Ví dụ 1: Feature đầy đủ cho Content module

**Input:**
"Thêm field type `color` cho content schema builder, API và form entry."

**Output style:**
1. Xác định ảnh hưởng: `content` module, schema contract, builder UI, validation,
   entry rendering, API serialization, tests.
2. Kế hoạch:
   - Update config field types + schema validator
   - Update builder inspector controls
   - Update entry form resolver
   - Add tests: builder, CRUD, API output
3. Triển khai + chạy test mục tiêu trước (`ContentSchemaBuilderTest`, `ContentEntryCrudTest`).
4. Bàn giao với risk note (backward compatibility schema cũ).

## Ví dụ 2: Core hardening feature

**Input:**
"Bổ sung retention cleanup command cho audit logs, có dry-run."

**Output style:**
1. Thiết kế command + service cleanup dùng `core.audit.retention_days`.
2. Có mode `--dry-run` để an toàn trước khi xóa thật.
3. Bổ sung test cho command behavior và redact policy không bị ảnh hưởng.
4. Cập nhật runbook với lịch chạy queue/cron.

## Ví dụ 3: Phân tích dự án trước khi estimate

**Input:**
"Phân tích kiến trúc dự án này và đề xuất cách thêm tính năng review workflow cho content."

**Output style:**
1. Tóm tắt kiến trúc modular + flow content hiện tại.
2. Xác định các extension points: state machine, revision, publish schedule, policies, hooks.
3. Đề xuất MVP chia pha + test matrix + migration path không phá dữ liệu cũ.

## Ví dụ 4: Bảo trì và fix bug production

**Input:**
"Trang tạo content entry thỉnh thoảng báo 500 khi publish schedule, giúp debug và fix."

**Output style:**
1. Tái hiện lỗi bằng test/steps tối thiểu, xác định điều kiện gây lỗi.
2. Khoanh vùng root cause (validation, timezone, queue payload, policy, site scope).
3. Áp dụng patch nhỏ nhất, giữ behavior hợp lệ không đổi.
4. Thêm regression test cho case lỗi và case hợp lệ gần kề.
5. Bàn giao với mức độ rủi ro + hướng monitor sau deploy.

# Constraints

- Chỉ dùng patterns đã phù hợp kiến trúc Pagify hiện tại; tránh tạo abstraction mới nếu chưa cần.
- Không bỏ qua authorization, audit, multi-site isolation trong bất kỳ feature mutation nào.
- Không hardcode secrets, tokens, credentials.
- Không thêm UI/flow ngoài yêu cầu khi user muốn scope tối thiểu.
- Ưu tiên thay đổi nhỏ, có test chứng minh, dễ review.
- Với bugfix, bắt buộc có bước reproduce và regression test trừ khi bất khả thi.

<!-- Generated by Skill Creator Ultra v1.0 -->
