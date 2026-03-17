# Pagify project profile (analyzed from current repository)

## 1) Dự án là gì

Pagify là CMS mã nguồn mở xây trên Laravel 12 + PHP 8.2, dùng kiến trúc modular
(`internachi/modular`) với các module chính:

- `core`: nền tảng auth, permissions, audit, locale, module runtime, event bus/hooks
- `media`: media domain (độc lập module)
- `updater`: update/lifecycle module

## 2) Kiến trúc runtime và module boundaries

- Root app dùng path repositories (`modules/*`) trong composer để phát triển
  module như package local.
- Mỗi module có cấu trúc nhất quán: `config/`, `database/`, `routes/`, `src/`,
  `resources/`, `tests/`.
- Route layer của module `content` thể hiện rõ middleware stack chuẩn:
  `ResolveSite`, `SetLocaleFromSite`, `EnsureModuleEnabled`, auth, audit,
  và API envelope middleware.

## 3) Contracts kỹ thuật quan trọng

### Security + authorization

- Permission model granular theo capability (vd: `core.admin.manage`,
  `media.asset.create`, `page-builder.page.publish`).
- Controllers/admin routes yêu cầu permission rõ ràng và có test matrix
  permission denied.

### Multi-site isolation

- Site context được resolve qua middleware + fallback policy.
- Feature mới có dữ liệu site-owned phải enforce boundary trong read/write/query.

### Audit + traceability

- Mutation routes được audit, có redact keys cho dữ liệu nhạy cảm.
- Thiết kế feature mới nên thêm metadata đủ để truy vết hành động.

### Queue-first cho tác vụ nặng

- Content schema builder không chạy DDL trực tiếp trong request; save tạo plan,
  queue job execute, status lifecycle rõ (`queued` → `applied` / `retryable`).

## 4) Frontend/admin shell conventions

- Admin UI dùng Inertia + theme động theo env:
  `ADMIN_THEMES_BASE_PATH`, `ADMIN_THEME`, `ADMIN_THEME_FALLBACK`.
- Mọi trang/action nên có đủ trạng thái: loading, empty, error, success, 403.
- Ưu tiên i18n dictionaries thay vì hardcoded strings.

## 5) Testing strategy trong repo

- Test suite giàu feature tests theo domain thực:
  - CRUD content type/entry
  - revision + rollback
  - publishing workflow
  - schema builder queue/execution
  - multi-site isolation hardening
  - permission denied matrix
- Pattern tốt: chạy test cụ thể theo feature trước, rồi mở rộng.

## 6) Checklist tương thích khi thêm feature mới

1. Có nằm đúng module boundary không?
2. Có FormRequest/resource/error-envelope consistency không?
3. Có permission + policy + denied test không?
4. Có audit metadata cho mutation không?
5. Có multi-site enforcement không?
6. Có queue-safe nếu là tác vụ nặng không?
7. Có test feature bám sát behavior user-facing không?
