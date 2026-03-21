---
description: "Use when user asks to plan implementation, create execution plan, or open planning issue. Enforces analysis-first flow, mandatory clarification before assumptions, and required use of pagify-planner skill."
name: "Planning Workflow Enforcement"
---

# Planning Workflow Enforcement

Khi người dùng yêu cầu lập kế hoạch, agent bắt buộc phải tuân theo quy trình sau để đảm bảo kế hoạch được phân tích kỹ lưỡng, rõ ràng và có thể thực thi:

1. Phân tích yêu cầu và phân tích codebase
- Xác định mục tiêu, phạm vi, rủi ro, ràng buộc.
- Đối chiếu với codebase để xác định phạm vi thay đổi thực tế.
- Đọc và xem xét tài liệu liên quan để hiểu bối cảnh kỹ thuật và nghiệp vụ trong thư mục `docs` (nếu có).

2. Đặt câu hỏi làm rõ trước khi đề xuất chi tiết
- Nếu còn bất kỳ điểm mơ hồ, thiếu thông tin, hoặc cần quyết định bổ sung, agent phải hỏi lại người dùng trước.
- Tuân thủ nghiêm ngặt: không tự ý thêm yêu cầu, giả định, hoặc nội dung ngoài phạm vi nếu chưa có sự đồng ý rõ ràng từ người dùng.

3. Sử dụng skill pagify-planner để tạo issue
- Bắt buộc sử dụng skill `pagify-planner` khi thực hiện tạo issue từ yêu cầu planning.
- Issue phải có đầy đủ Todo List và Acceptance Criteria.
- Gán labels/type phù hợp với loại công việc và phạm vi module.
- Bắt buộc dùng GitHub tool để tạo issue, ưu tiên `mcp_io_github_git_issue_write` với `method: create`.
- Không dùng GitHub CLI làm mặc định cho thao tác tạo issue.
- Tạo issue ngay sau khi hoàn tất bước phân tích và phân rã, không yêu cầu người dùng chốt lại trước khi tạo.
- Sau khi tạo issue thành công, chỉ phản hồi đúng 2 thông tin: tên issue và URL issue.

Nếu không thể tạo issue trực tiếp do thiếu quyền GitHub, agent phải trả về nội dung issue hoàn chỉnh và phần cập nhật trạng thái để người dùng áp dụng thủ công.
