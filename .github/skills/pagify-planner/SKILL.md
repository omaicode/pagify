---
name: pagify-planner
description: 'Lập kế hoạch phát triển cho Pagify và tạo GitHub issue đầy đủ. Dùng khi cần phân rã công việc, tạo issue có labels/type, todo list, acceptance criteria, và theo dõi trạng thái thực thi.'
argument-hint: 'Bạn muốn lập kế hoạch cho hạng mục nào? (feature/bugfix/refactor/chore)'
user-invocable: true
---

# Pagify Planner

## Mục Tiêu

Skill này chuẩn hóa quy trình lập kế hoạch để tạo GitHub issue có cấu trúc rõ ràng, dễ thực thi và dễ theo dõi trạng thái từ lúc mở issue đến khi code hoàn tất.

## Khi Nào Sử Dụng

- Khi cần chuyển yêu cầu thành kế hoạch triển khai.
- Khi cần tạo issue mới trên GitHub với nhãn và type phù hợp.
- Khi cần issue có checklist công việc và acceptance criteria rõ ràng.
- Khi cần đồng bộ tiến độ code vào issue trong quá trình thực hiện.

## Input Kỳ Vọng

- Mục tiêu nghiệp vụ hoặc vấn đề cần xử lý.
- Loại công việc: `feature`, `bugfix`, `refactor`, `chore`, `security`, `docs`.
- Phạm vi tác động: module, API/UI, dữ liệu, migration, vận hành.
- Ràng buộc kỹ thuật và deadline (nếu có).

## Quy Trình Thực Thi

1. Làm rõ phạm vi và kết quả cần đạt
- Tóm tắt yêu cầu thành 1-2 câu objective.
- Xác định in-scope và out-of-scope.
- Liệt kê rủi ro/chặn phụ thuộc chính.

2. Phân rã kế hoạch thực thi
- Tách công việc thành các bước có thể kiểm chứng.
- Mỗi bước phải là hành động cụ thể, có đầu ra rõ ràng.
- Ưu tiên thứ tự theo phụ thuộc kỹ thuật.

3. Gán type và labels
- `type:feature` cho chức năng mới.
- `type:bug` cho sửa lỗi hành vi sai.
- `type:refactor` cho tái cấu trúc không đổi hành vi.
- `type:chore` cho việc bảo trì/hạ tầng.
- Bổ sung labels theo phạm vi, ví dụ: `module:core`, `area:api`, `area:admin-ui`, `priority:high`.

4. Soạn nội dung issue chuẩn
- Issue bắt buộc có các phần sau:
  - Summary
  - Context / Problem
  - Scope
  - Todo List (checkbox)
  - Acceptance Criteria
  - Risks / Notes

5. Tạo issue trên GitHub
- Bắt buộc dùng GitHub tool để tạo issue (không dùng CLI thủ công làm mặc định).
- Tool ưu tiên:
  - `mcp_io_github_git_issue_write` với `method: create` để tạo issue mới.
  - Truyền đầy đủ `title`, `body`, `labels`, và `type` (nếu repo hỗ trợ issue type).
- Tạo issue ngay sau khi hoàn tất bước phân tích và phân rã, không yêu cầu người dùng chốt lại trước khi tạo.
- Sau khi tạo issue thành công, chỉ phản hồi đúng 2 thông tin:
  - Tên issue
  - URL issue
- Nếu môi trường không có quyền ghi GitHub hoặc tool thất bại do quyền truy cập, trả về nội dung issue hoàn chỉnh để người dùng tạo thủ công.

6. Đồng bộ trạng thái trong lúc triển khai
- Khi bắt đầu code: chuyển issue sang trạng thái `in-progress` (theo label/project field nếu có).
- Khi hoàn thành từng hạng mục: cập nhật checkbox tương ứng trong Todo List.
- Khi hoàn tất code + test: cập nhật trạng thái `ready for review` hoặc `done` theo quy ước dự án.

## Mẫu Issue

```md
## Summary
<Mục tiêu thay đổi>

## Context / Problem
<Vấn đề hiện tại, tác động và nguyên nhân nếu đã biết>

## Scope
- In scope:
  - ...
- Out of scope:
  - ...

## Todo List
- [ ] Phân tích yêu cầu và xác nhận phạm vi
- [ ] Thiết kế giải pháp (API/UI/domain)
- [ ] Triển khai code
- [ ] Bổ sung/cập nhật test
- [ ] Tự kiểm tra và cập nhật tài liệu liên quan

## Acceptance Criteria
- [ ] Hành vi mới/sửa lỗi hoạt động đúng theo yêu cầu
- [ ] Không gây hồi quy các luồng liên quan
- [ ] Test liên quan pass
- [ ] Tài liệu/changelog được cập nhật khi cần

## Risks / Notes
- <Rủi ro chính và phương án giảm thiểu>
```

## Tiêu Chí Hoàn Tất

- Issue được tạo với title, body, labels/type phù hợp.
- Issue có Todo List và Acceptance Criteria rõ ràng, kiểm chứng được.
- Trong quá trình code, checkbox Todo được cập nhật theo tiến độ thực tế.
- Khi hoàn tất, trạng thái issue phản ánh đúng trạng thái triển khai.
- Phản hồi sau khi tạo issue chỉ gồm tên issue và URL, không gửi lại toàn bộ nội dung issue.

## Prompt Mẫu

- `/pagify-planner Lập kế hoạch và tạo issue cho tính năng phân quyền nâng cao module core.`
- `/pagify-planner Tạo issue bugfix cho lỗi reset password hết hạn token nhưng báo sai thông điệp.`
- `/pagify-planner Tạo issue refactor cho admin group service, có todo checklist và acceptance criteria.`
