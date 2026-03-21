---
description: Các hướng dẫn này dành cho kỹ sư phát triển tính năng của Pagify, giúp họ hiểu rõ quy trình và tài nguyên cần thiết để triển khai các tính năng mới, bảo trì hệ thống, và sửa lỗi hiệu quả.
applyTo: '**'
---
## Project overview

- Backend: Laravel 12 (PHP 8.2)
- Admin panel: Inertia + Vue 3 + Tailwind CSS
- Public site: Twig engine
- Database: MySQL, sử dụng Eloquent ORM

## Architectural conventions

- Các modules sẽ được tổ chức theo domain (ví dụ: core, media, page-builder, updater) sử dụng thư viện `internachi/modular` đặt trong thư mục `modules`. Mỗi module sẽ có cấu trúc riêng với các thư mục như `config`, `database`, `resources`, `routes`, `src`, `tests`.
    - Ví dụ, module `core` sẽ có đường dẫn `modules/core` và chứa các thành phần liên quan đến cốt lõi của hệ thống.
    - Module `media` sẽ quản lý tất cả các chức năng liên quan đến media, bao gồm upload, xử lý và lưu trữ media.
    - Module `page-builder` sẽ chứa logic liên quan đến trình xây dựng trang, bao gồm các thành phần như templates, blocks, và rendering.
    - Module `updater` sẽ quản lý các chức năng liên quan đến cập nhật hệ thống, bao gồm việc kiểm tra phiên bản, tải về và cài đặt các bản cập nhật.
- Services sẽ được xây dựng theo nguyên tắc Single Responsibility Principle (SRP) và sẽ được đặt trong thư mục `src/Services` của mỗi module. Mỗi service sẽ có một nhiệm vụ cụ thể và sẽ được thiết kế để dễ dàng mở rộng và bảo trì. Không trực tiếp kết nối đến database trong controllers. Thay vào đó, controllers sẽ gọi các service để thực hiện logic nghiệp vụ, và các service sẽ tương tác với database thông qua Eloquent models hoặc repositories.
- Repositories sẽ được sử dụng để tách biệt logic truy cập dữ liệu khỏi logic nghiệp vụ. Chúng sẽ được đặt trong thư mục `src/Repositories` của mỗi module và sẽ cung cấp các phương thức để truy xuất và thao tác với dữ liệu từ database.

## Admin (Inertia + Vue 3 + Tailwind CSS)

Giao diện admin được đặt tại thư mục `themes/admin/{theme_name}` và tuân theo các quy ước sau:

- Các trang được đặt tại `resources/js/Pages/...`.
- Bố cục trang dùng chung đặt tại `resources/js/Layouts/AdminLayout.vue`.
- Components dùng chung đặt tại `resources/js/Components/...`.
- Sử dụng script setup + TypeScript, defineProps/defineEmits, composables trong `resources/js/Composables`.
- Forms sử dụng Inertia form helper và server-side validation thông qua FormRequest.

## Public site (Twig engine)

Giao diện public site được đặt tại thư mục `themes/main/{theme_name}` và tuân theo các quy ước sau:

- Các template Twig được đặt tại `resources/views/...`.
- Sử dụng các blocks và extends để tái sử dụng layout và components.
- Sử dụng các filters và functions của Twig để xử lý dữ liệu hiển thị.
- Tất cả logic xử lý dữ liệu nên được thực hiện trong backend và truyền dữ liệu đã được xử lý đến Twig templates để đảm bảo separation of concerns và tối ưu hiệu suất.

## Coding standards

- Tuân thủ PSR-12, prefer dependency injection, avoid facades except config, log, auth.
- Sử dụng PHP 8+ typed properties và return types.
- Đối với Vue:
  - Chỉ sử dụng Composition API, không sử dụng Options API.
  - Sử dụng <script setup> và type props/emit với TypeScript.
  - Sử dụng các lớp tiện ích của Tailwind CSS trong templates.

## Testing

- Feature tests trong các module sẽ được đặt trong thư mục `modules/{module_name}/tests/Feature`.
- Unit tests sẽ được đặt trong thư mục `modules/{module_name}/tests/Unit`.
- Luôn thêm tests cho các controllers, services và policies mới.

## How to use these instructions

- Khi được yêu cầu làm việc trên repo này, luôn:
  1. Tuân thủ kiến trúc ở trên.
  2. Ưu tiên thêm các service/repository mới thay vì đặt logic trong controllers.
  3. Đối với giao diện admin, tạo các trang Inertia Vue dưới module đúng.
  4. Đối với trang công khai, tạo các template Twig, không phải Blade hoặc Vue.

## Skill routing

- Khi người dùng yêu cầu lập kế hoạch triển khai hoặc tạo issue planning: bắt buộc dùng skill `pagify-planner`.
- Khi người dùng yêu cầu làm việc trên module `core`: ưu tiên dùng skill `pagify-module-core`.
- Khi người dùng yêu cầu làm việc trên module `media`: ưu tiên dùng skill `pagify-module-media`.
- Khi người dùng yêu cầu làm việc trên module `page-builder`: ưu tiên dùng skill `pagify-module-page-builder`.
- Khi người dùng yêu cầu làm việc trên module `updater`: ưu tiên dùng skill `pagify-module-updater`.
- Nếu yêu cầu liên quan nhiều module, chọn skill theo module chính và nêu rõ phạm vi tích hợp chéo trước khi triển khai.
- Nếu không rõ module mục tiêu, phải hỏi lại để xác nhận trước khi chọn skill.

## Issue lifecycle sync

- Khi task có gắn GitHub Issue (issue number hoặc URL), agent phải đồng bộ tiến độ vào issue trong quá trình làm việc.
- Khi bắt đầu triển khai code: cập nhật trạng thái issue sang `in-progress` (qua label/type/project field tùy setup hiện có).
- Sau khi hoàn thành từng hạng mục trong Todo list của issue: cập nhật checkbox tương ứng.
- Khi hoàn tất code và kiểm thử liên quan: cập nhật trạng thái issue sang `ready for review` hoặc `done` theo workflow của repo.
- Nếu môi trường không có quyền ghi lên GitHub (thiếu token/quyền), agent phải trả về phần cập nhật đề xuất để người dùng áp dụng thủ công.
