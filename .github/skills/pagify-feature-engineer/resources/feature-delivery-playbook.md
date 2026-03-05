# Feature delivery playbook for Pagify

## Phase A — Clarify scope

Thu thập đủ 6 thông tin trước khi code:

1. Trigger: user action nào khởi động feature?
2. Input: dữ liệu vào từ đâu, format gì?
3. Output: API/UI/state thay đổi như thế nào?
4. Rules: business rules bắt buộc?
5. Edge cases: lỗi/permission/site boundary?
6. Done criteria: điều kiện nghiệm thu cụ thể?

## Phase B — Impact mapping

Map impact theo layers:

- DB: migration/index/constraints
- Domain: model/service/action/events
- HTTP: requests/controllers/resources/routes/middleware
- UI: pages/components/i18n/states
- Async: jobs/commands/retry/idempotency
- Tests: feature/unit/regression

## Phase C — Implementation order

1. Schema + model changes
2. Domain logic
3. HTTP contracts
4. UI integration
5. Queue/events/audit wiring
6. Tests + docs

## Phase D — Verification

1. Chạy test nhỏ nhất liên quan thay đổi.
2. Chạy test nhóm module bị ảnh hưởng.
3. Chỉ chạy full suite khi cần xác nhận regression rộng.

## Phase E — Maintenance & bugfix sequence

1. Reproduce trước khi sửa:
	- Tạo test thất bại hoặc steps tái hiện rõ ràng.
2. Chẩn đoán root cause:
	- Khoanh vùng theo DB/Domain/HTTP/UI/Queue.
3. Áp dụng minimal patch:
	- Sửa điểm gây lỗi, tránh refactor lan rộng không cần thiết.
4. Thêm regression test:
	- Khóa lại bug đã xảy ra + 1 case lân cận.
5. Handoff production:
	- Ghi rõ risk, monitor metric/log, và phương án rollback nếu cần.

## Best practices

- Ưu tiên incremental PR nhỏ, mỗi PR có mục tiêu duy nhất.
- Giữ backward compatibility cho schema/data cũ khi có thể.
- Với destructive operations, luôn có confirm hoặc dry-run.
- Với admin UI, chuẩn hóa confirm bằng SweetAlert2 và feedback mutation bằng toast success/error.
- Viết handoff ngắn gọn: changed/why/how-to-verify/risks.

## Pitfalls

- Overfit cho một edge case và làm rule phình to.
- Bỏ sót `RecordAuditLog` hoặc policy trong route mới.
- Dùng query trực tiếp phá site scope consistency.
- Không test permission denied dẫn tới lỗ hổng truy cập.
- Không có bước reproduce nên khó xác nhận fix đúng nguyên nhân.
