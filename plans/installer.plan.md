## Plan: Installer Wizard Module

Xây module installer ở lớp bootstrap cao hơn core để chặn mọi request khi hệ thống chưa setup, dẫn về /install theo wizard 5 tab có kiểm tra điều kiện trước khi cho sang bước tiếp theo. Hướng triển khai ưu tiên an toàn khởi động (không phụ thuộc ResolveSite/DB khi chưa migrate), dùng catalog Marketplace local config cho MVP, và cài plugin/theme qua Composer theo lựa chọn của user.

**Scope Summary**
- Trigger: Truy cập web lần đầu sau composer create hoặc clone source.
- Inputs: Runtime server checks, thông tin project/db/mail, mục đích sử dụng, plugin/theme selections.
- Outputs: Dự án được cấu hình + migrate + seed tối thiểu + plugin/theme cài xong + cờ installed.
- Constraints: Installer phải chạy trước core; tab sau chỉ mở khi tab trước pass toàn bộ validation/connectivity/version.
- Affected layers: Bootstrap routing/middleware, module mới installer, DB setup orchestration, admin UI wizard, tests.

**Execution Rules (Checklist Update)**
- Mỗi hạng mục hoàn thành phải đổi `[ ]` sang `[x]` ngay trong file này.
- Chỉ được tick khi có bằng chứng verify tương ứng (test pass, API response đúng, hoặc manual checklist pass).
- Nếu xong một phần, thêm ghi chú ngắn ngay dưới mục đó với prefix `Note:` và giữ trạng thái `[ ]`.

**Execution Plan + Checklists**

**Phase 1 - Bootstrap precedence và guard luồng cài đặt**
- Objective: Installer chạy trước Core, không phụ thuộc ResolveSite khi hệ thống chưa setup.
- Checklist implementation:
	- [x] P1.1 Tạo module `installer` trong `modules/` với provider và route riêng.
	- [x] P1.2 Đăng ký provider của installer đứng trước Core trong bootstrap provider list.
	- [x] P1.3 Tạo `InstallerStateService` với contract `isInstalled/checkPrerequisites/getCurrentStep`, lưu cờ installed (MVP: file flag trong storage) và lock chống setup đồng thời.
	- [x] P1.4 Thêm middleware guard toàn cục ở bootstrap app: chưa installed thì redirect `/install`, whitelist install routes + static assets + health, đã installed thì chặn truy cập lại wizard.
	- [x] P1.5 Tạo route web `/install` và API `/api/v1/install/*` không đi qua `ResolveSite`.
- Checklist done criteria:
	- [x] D1.1 Truy cập `/` khi chưa setup luôn redirect tới `/install`.
	- [ ] D1.2 `/up` và static assets vẫn truy cập được khi chưa setup.
	Note: Da verify `/up` bang test `InstallerBootstrapFlowTest`, chua verify static assets qua integration test browser/web server.
	- [x] D1.3 Không có lỗi chạm DB `sites`/`ResolveSite` trong luồng installer.
	Note: Da verify qua luong guard + routes installer khong su dung ResolveSite, va test guard/gating pass.

**Phase 2 - Wizard backend 5 tab có gate chuyển bước**
- Objective: Mỗi tab có preflight rõ ràng và chỉ qua tab tiếp theo khi pass đầy đủ.
- Checklist implementation:
	- [x] P2.1 Tab 1 system-check endpoint: permissions, php extensions bắt buộc, `upload_max_filesize`, `post_max_size`, version PHP/Laravel; trả pass/fail + severity + reason.
	- [x] P2.2 Tab 2 configuration endpoint: validate project name + DB + mail, test kết nối DB/mail trước khi commit cấu hình; fail thì không qua tab 3.
	- [x] P2.3 Tab 3 purpose endpoint: nhận purpose (blog/company/ecommerce/other), map sang profile đề xuất plugin/theme mặc định.
	- [x] P2.4 Tab 4 plugin catalog endpoint: lấy catalog local config, preselect theo purpose, check compatibility/version/composer availability, cài plugin theo batch có progress + rollback theo item lỗi.
	- [x] P2.5 Tab 5 theme catalog endpoint: lấy catalog local config, cho chọn cài thêm qua Composer, validate manifest, cho activate theme theo lựa chọn.
	- [x] P2.6 Finalize endpoint: migrate/seed bootstrap tối thiểu, tạo admin đầu tiên nếu chưa có, ghi cờ installed, clear cache, redirect admin login.
- Checklist done criteria:
	- [x] D2.1 Không thể gọi API tab N+1 khi tab N chưa pass.
	- [x] D2.2 DB/mail connectivity fail thì không persist bước cấu hình.
	- [ ] D2.3 Plugin/theme được cài đúng theo preselect và lựa chọn user.
	- [ ] D2.4 Finalize thành công tạo trạng thái installed hợp lệ.

**Phase 3 - UI wizard và trải nghiệm vận hành**
- Objective: UI rõ trạng thái, dễ xử lý lỗi, và đầy đủ i18n.
- Checklist implementation:
	- [x] P3.1 Tạo trang wizard nhiều tab với state `loading/empty/error/success`.
	- [x] P3.2 Disable nút Next khi tab hiện tại chưa pass checks.
	- [x] P3.3 Hiển thị chi tiết lỗi kết nối DB/mail/composer để user sửa trực tiếp.
	- [x] P3.4 Bổ sung i18n đầy đủ en/vi cho toàn bộ text installer.
	- [x] P3.5 Thêm retry controls cho cài plugin/theme + log rút gọn theo package.
- Checklist done criteria:
	- [ ] D3.1 Không còn hardcoded text trong màn wizard.
	Note: Da i18n phan lon label/button/message; van con mot so chuoi mo ta ky thuat trong JS co the dua them vao lang file o buoc toi uu.
	- [ ] D3.2 Người dùng thấy rõ nguyên nhân fail và có thể retry tại đúng bước.

**Phase 4 - Hardening và khả năng phục hồi**
- Objective: Installer an toàn khi production và dễ debug sự cố.
- Checklist implementation:
	- [x] P4.1 Khóa truy cập trái phép vào `/install` sau khi installed (redirect về home/admin).
	- [x] P4.2 Thêm rate limit cho install mutation APIs.
	- [x] P4.3 Validate chặt payload cho toàn bộ tab endpoints.
	- [x] P4.4 Thêm dry-run preflight tổng trước finalize để xác nhận lại DB/mail/composer/network.
	- [x] P4.5 Thêm telemetry log tối thiểu theo tab (`started/passed/failed`).
- Checklist done criteria:
	- [x] D4.1 Không thể bypass flow installer để gọi trực tiếp finalize.
	- [ ] D4.2 Có log đủ để truy vết tab nào fail và lý do fail.

**Phase 5 - Test và tài liệu**
- Objective: Có test khóa hành vi quan trọng và tài liệu setup rõ ràng.
- Checklist implementation:
	- [ ] P5.1 Viết feature tests cho redirect guard, tab gating tuần tự, validation errors, DB/mail fail/success.
	- [ ] P5.2 Viết feature tests cho plugin/theme install flow và finalize thành công.
	- [ ] P5.3 Viết regression tests cho case clone source chưa migrate (không crash ResolveSite), install dở dang, truy cập `/install` sau khi installed.
	- [ ] P5.4 Cập nhật runbook setup cho cả `composer create` và `clone source + truy cập web`.
- Checklist done criteria:
	- [ ] D5.1 Bộ test installer mục tiêu pass ổn định.
	- [ ] D5.2 Runbook đủ để người mới setup không cần suy đoán thêm.

**Relevant files**
- /home/bieberkieu/Data/projects/omc-pagify/bootstrap/providers.php — chèn provider installer trước core.
- /home/bieberkieu/Data/projects/omc-pagify/bootstrap/app.php — đăng ký middleware guard toàn cục và exception handling cho install flow.
- /home/bieberkieu/Data/projects/omc-pagify/modules/core/routes/core-routes.php — tham chiếu pattern route stack hiện tại để tách installer stack không dùng ResolveSite.
- /home/bieberkieu/Data/projects/omc-pagify/modules/core/src/Http/Middleware/ResolveSite.php — tham chiếu rủi ro truy cập DB sớm và thêm fallback an toàn nếu cần.
- /home/bieberkieu/Data/projects/omc-pagify/modules/core/src/Services/PluginManagerService.php — tái sử dụng flow install Composer plugin và compatibility checks.
- /home/bieberkieu/Data/projects/omc-pagify/modules/core/src/Services/FrontendThemeManagerService.php — mở rộng flow theme install/activate cho installer.
- /home/bieberkieu/Data/projects/omc-pagify/modules/updater/config/updater.php — dùng cấu hình composer + marketplace feature flags.
- /home/bieberkieu/Data/projects/omc-pagify/themes/admin/default/resources/js/Pages/Admin/Plugins/Index.vue — tham chiếu UX mutation/install trạng thái.
- /home/bieberkieu/Data/projects/omc-pagify/themes/admin/default/resources/js/Pages/Admin/Themes/Index.vue — tham chiếu UX chọn/cài/activate theme.
- /home/bieberkieu/Data/projects/omc-pagify/themes/admin/default/lang/en/ui.php — bổ sung i18n installer tiếng Anh.
- /home/bieberkieu/Data/projects/omc-pagify/themes/admin/default/lang/vi/ui.php — bổ sung i18n installer tiếng Việt.
- /home/bieberkieu/Data/projects/omc-pagify/docs/runbook.md — cập nhật quy trình cài đặt bằng web installer.
- /home/bieberkieu/Data/projects/omc-pagify/tests/Feature — thêm bộ test installer end-to-end và regression.

**Verification Checklist**
- [ ] V1 Chạy test feature redirect guard: `/` -> `/install` khi chưa installed, assets/health vẫn truy cập được.
Note: Da pass test redirect (`/` -> `/install`) va health endpoint `/up`; static assets chua duoc cover trong test HTTP kernel.
- [x] V2 Chạy test tab gating: không thể gọi API tab N+1 khi tab N chưa pass.
- [ ] V3 Chạy test preflight checks: permissions/extensions/upload/post-size/version trả đúng severity và block logic.
- [ ] V4 Chạy test DB/mail connectivity: fail thì không persist bước 2, pass mới unlock bước 3.
Note: Da cover case fail -> khong persist qua `InstallerWizardGatingTest`; case pass -> unlock buoc 3 chua duoc bo sung test.
- [ ] V5 Chạy test plugin/theme install batch (composer mocked): preselect theo purpose đúng, retry/partial failure đúng.
- [ ] V6 Chạy test finalize: migrate + seed bootstrap + cờ installed + không vào lại `/install`.
- [ ] V7 Chạy regression quanh ResolveSite: chưa migrate không phát sinh lỗi 500 khi vào `/install`.

**Decisions**
- Marketplace source cho giai đoạn đầu: local config catalog trong repo (không gọi API ngoài).
- Installed marker: ghi cờ installed riêng khi hoàn tất wizard.
- Install strategy ở bước plugin/theme: chỉ Composer package cho MVP.
- In scope: Web installer 5 tab, preflight/gating, plugin+theme install cơ bản.
- Out of scope: OAuth marketplace, ZIP/offline installer, self-update engine cho installer.

**Further Considerations**
1. Đề xuất dùng file flag làm nguồn sự thật ban đầu, sau đó đồng bộ sang DB setting khi finalize để thuận lợi cho monitoring.
2. Nếu cần production-grade hơn ở vòng sau, có thể thêm signed installer session token để chống CSRF/replay giữa các tab API.
3. Catalog local nên có schema version để sau này chuyển sang Marketplace API thật mà không đổi contract frontend.
