# BÁO CÁO KIỂM THỬ TIỀN PHÁT HÀNH (PRE-PRODUCTION AUDIT & REMEDIATION REPORT)
**Dự án:** Website Thương mại Điện tử Hương Mít (`haomilk`)  
**Ngày Audit ban đầu:** 11/11/2026  
**Ngày cập nhật sau Security Patch:** 12/11/2026  
**Vai trò thực hiện:** Senior Laravel Security, Payment Integration & QA Automation Engineer  
**Phiên bản nền tảng:** Laravel 13 | PHP 8.3 | MySQL 8.0 (Laragon Local) / SQLite `:memory:` (PHPUnit)  
**Dung lượng tải mục tiêu:** 50 – 200 lượt truy cập/ngày (tối đa ~100 người dùng đồng thời)

---

## 1. TỔNG QUAN KẾT QUẢ KIỂM THỬ (EXECUTIVE SUMMARY)

### So sánh Trước và Sau khi Vá Lỗi (Remediation Comparison)

| Chỉ số | Trước khi vá (11/11/2026) | Sau khi vá (12/11/2026) |
| :--- | :---: | :---: |
| **Tổng số bài test tự động (`PreProductionAuditTest`)** | 14 tests (7 PASS / 7 FAIL) | **24 tests / 24 PASS (128 assertions)** |
| **Toàn bộ Test Suite PHPUnit** | 46 test cũ + 7 pass / 7 fail audit | **70 tests / 70 PASS (337 assertions)** |
| **Lỗ hổng Critical (P0)** | 2 chưa vá (`SEC-01`, `SEC-02`) | **0 (Đã vá & kiểm thử hồi quy)** |
| **Lỗ hổng High (P1)** | 4 chưa vá (`SEC-06`, `SEC-07`, `RATE-01`, `RACE-01`) | **0 (Đã vá & kiểm thử hồi quy)** |
| **Vấn đề Medium (P2)** | 4 chưa vá (`ORD-02`, `RATE-02`, `PERF-01`, `CONF-01`) | **0 trong mã nguồn (Đã vá & kiểm thử)** |
| **Kiểm chuẩn định dạng (`Laravel Pint`)** | Chưa chuẩn hóa toàn bộ | **PASS** trên toàn bộ file PHP đã sửa |
| **Tình trạng triển khai** | ⛔ NO-GO | 🟡 **ĐẠT CHUẨN DEPLOY STAGING** *(Chưa GO Production cho đến khi hoàn tất kiểm thử tải/tích hợp thật trên Staging)* |

---

## 2. KẾT LUẬN ĐÁNH GIÁ SẴN SÀNG (READINESS VERDICT)

> [!IMPORTANT]
> **ĐÁNH GIÁ HIỆN TẠI: SẴN SÀNG TRIỂN KHAI STAGING (READY FOR STAGING) — CHƯA GO-LIVE PRODUCTION.**
> - Toàn bộ lỗ hổng bảo mật P0, P1 và lỗi nghiệp vụ/hiệu năng P2 phát hiện ở giai đoạn Audit đã được vá triệt để trong mã nguồn và xác nhận bằng **70/70 bài test tự động (`337 assertions`)** trên môi trường test độc lập (SQLite `:memory:`).
> - Để được phép **GO Production**, đội vận hành cần triển khai lên môi trường **Staging có HTTPS + MySQL thật**, chạy checklist kiểm thử thủ công (test Webhook SePay thật qua HTTPS, kiểm thử đồng thời đa luồng trên MySQL 8 InnoDB, kiểm tra backup/restore database và thiết lập biến môi trường bí mật mới trên server).

---

## 3. TRẠNG THÁI XÁC MINH THEO TỪNG HẠNG MỤC

| Mã | Mức độ | Hạng mục | Trạng thái | Phương pháp xác minh | Vị trí code liên quan |
| :--- | :---: | :--- | :---: | :--- | :--- |
| **SEC-01** | Critical (P0) | Xác thực Webhook SePay (Fail-closed, Header API Key, `hash_equals`, HMAC-SHA256 raw body, chống replay, lọc log token) | **PASS** | Automated Test + Code Review | [`PaymentWebhookController.php`](file:///c:/laragon/www/haomilk/app/Http/Controllers/PaymentWebhookController.php), [`config/bank.php`](file:///c:/laragon/www/haomilk/config/bank.php), [`BankPaymentSession.php`](file:///c:/laragon/www/haomilk/app/Models/BankPaymentSession.php) |
| **SEC-02** | Critical (P0) | Khóa hoàn toàn endpoint giả lập thanh toán khỏi Staging/Production (kể cả khi `APP_DEBUG=true`) | **PASS** | Automated Test + CLI `route:list` (`--env=staging`, `--env=production`) + Code Review | [`routes/web.php`](file:///c:/laragon/www/haomilk/routes/web.php), [`CheckoutController.php`](file:///c:/laragon/www/haomilk/app/Http/Controllers/CheckoutController.php), [`bank_payment.blade.php`](file:///c:/laragon/www/haomilk/resources/views/checkout/bank_payment.blade.php) |
| **SEC-03** | High (P1) | IDOR xem đơn hàng của người dùng khác | **PASS** | Automated Test | [`PreProductionAuditTest.php`](file:///c:/laragon/www/haomilk/tests/Feature/PreProductionAuditTest.php) |
| **SEC-04** | High (P1) | IDOR xóa địa chỉ của người dùng khác | **PASS** | Automated Test | [`PreProductionAuditTest.php`](file:///c:/laragon/www/haomilk/tests/Feature/PreProductionAuditTest.php) |
| **SEC-05** | High (P1) | Khóa tài khoản sau 5 lần sai + IP/Email Rate Limiter 2 lớp | **PASS** | Automated Test | [`AuthController.php`](file:///c:/laragon/www/haomilk/app/Http/Controllers/Auth/AuthController.php), [`AppServiceProvider.php`](file:///c:/laragon/www/haomilk/app/Providers/AppServiceProvider.php) |
| **SEC-06** | High (P1) | Ngăn Admin thao tác lên tài khoản Admin khác qua `Admin\CustomerController` | **PASS** | Automated Test + Code Review | [`CustomerController.php`](file:///c:/laragon/www/haomilk/app/Http/Controllers/Admin/CustomerController.php), [`AdminMiddleware.php`](file:///c:/laragon/www/haomilk/app/Http/Middleware/AdminMiddleware.php) |
| **SEC-07** | High (P1) | SePay Webhook kiểm tra `transferType=in`, `accountNumber` thụ hưởng, thiếu/dư tiền, hết hạn & đối soát | **PASS** | Automated Test + Code Review | [`PaymentWebhookController.php`](file:///c:/laragon/www/haomilk/app/Http/Controllers/PaymentWebhookController.php), [`BankPaymentSession.php`](file:///c:/laragon/www/haomilk/app/Models/BankPaymentSession.php) |
| **RATE-01** | High (P1) | Rate limit `/chatbot/message` (12/phút, 120/ngày), khôi phục CSRF token, chuyển Gemini API Key sang Header & bật SSL verify trên Staging/Prod | **PASS** | Automated Test + Code Review | [`bootstrap/app.php`](file:///c:/laragon/www/haomilk/bootstrap/app.php), [`routes/web.php`](file:///c:/laragon/www/haomilk/routes/web.php), [`AppServiceProvider.php`](file:///c:/laragon/www/haomilk/app/Providers/AppServiceProvider.php), [`GeminiService.php`](file:///c:/laragon/www/haomilk/app/Services/GeminiService.php), [`config/services.php`](file:///c:/laragon/www/haomilk/config/services.php) |
| **RATE-02** | Medium (P2) | IP & Email Rate Limiter cho đăng nhập (`login`) và đăng ký (`register`), hỗ trợ cấu hình `TRUSTED_PROXIES` | **PASS** | Automated Test + Code Review | [`AppServiceProvider.php`](file:///c:/laragon/www/haomilk/app/Providers/AppServiceProvider.php), [`routes/web.php`](file:///c:/laragon/www/haomilk/routes/web.php), [`bootstrap/app.php`](file:///c:/laragon/www/haomilk/bootstrap/app.php) |
| **RACE-01** | High (P1) | Chống overselling đặt hàng COD (`DB::transaction` + `lockForUpdate` + atomic `decrement`), khóa thứ tự biến thể & chống hoàn tồn 2 lần khi hủy đơn | **PASS (Logic)** / **BLOCKED (Multi-thread MySQL live stress)** | Automated Test (SQLite) + Code Review | [`CheckoutController.php`](file:///c:/laragon/www/haomilk/app/Http/Controllers/CheckoutController.php), [`OrderController.php`](file:///c:/laragon/www/haomilk/app/Http/Controllers/Admin/OrderController.php), [`BankPaymentSession.php`](file:///c:/laragon/www/haomilk/app/Models/BankPaymentSession.php) |
| **ORD-01** | High (P1) | Hoàn tồn kho & ghi sổ `InventoryTransaction` đúng 1 lần khi hủy đơn | **PASS** | Automated Test | [`OrderController.php`](file:///c:/laragon/www/haomilk/app/Http/Controllers/Admin/OrderController.php) |
| **ORD-02** | Medium (P2) | Tự động tất toán đơn COD đã cọc 40% (`deposit_paid` -> `paid`, tạo bản ghi `Payment` phần 60%) khi giao thành công | **PASS** | Automated Test | [`OrderController.php`](file:///c:/laragon/www/haomilk/app/Http/Controllers/Admin/OrderController.php) |
| **PERF-01** | Medium (P2) | Khắc phục N+1 query trên `Product`, `ProductVariant` (`activeSaleItems`, fallback `images` rỗng) -> giảm từ 167 queries xuống <= 15 queries trang chủ | **PASS** | Automated Test (`DB::getQueryLog()`) | [`Product.php`](file:///c:/laragon/www/haomilk/app/Models/Product.php), [`ProductVariant.php`](file:///c:/laragon/www/haomilk/app/Models/ProductVariant.php), [`HomeController.php`](file:///c:/laragon/www/haomilk/app/Http/Controllers/HomeController.php), [`CartController.php`](file:///c:/laragon/www/haomilk/app/Http/Controllers/CartController.php), [`CheckoutController.php`](file:///c:/laragon/www/haomilk/app/Http/Controllers/CheckoutController.php), [`ChatbotService.php`](file:///c:/laragon/www/haomilk/app/Services/ChatbotService.php) |
| **CONF-01** | Medium (P2) | Tạo mẫu cấu hình an toàn cho Staging & Production, ép `URL::forceScheme('https')`, loại bỏ token cứng khỏi `.env.example` & `config/bank.php` | **PASS (Template & Code)** / **NOT RUN (Live Server)** | Automated Test + File Inspection | [`.env.example`](file:///c:/laragon/www/haomilk/.env.example), [`.env.staging.example`](file:///c:/laragon/www/haomilk/.env.staging.example), [`.env.production.example`](file:///c:/laragon/www/haomilk/.env.production.example), [`AppServiceProvider.php`](file:///c:/laragon/www/haomilk/app/Providers/AppServiceProvider.php) |

---

## 4. MA TRẬN PHÂN LOẠI THEO 10 NHÓM KIỂM THỬ (SAU REMEDIATION)

| STT | Nhóm kiểm thử | Trạng thái sau vá | Ghi chú xác minh |
| :---: | :--- | :---: | :--- |
| **1** | **Authentication, authorization, IDOR** | **PASS (Code/Test)** | Đã chặn leo thang quyền `CustomerController` (`SEC-06`), khóa endpoint giả lập trên staging/prod (`SEC-02`), hỗ trợ JSON 401/403 trong `AdminMiddleware`. |
| **2** | **Rate limit, chống brute force, spam** | **PASS (Code/Test)** | Đã gán `throttle:chatbot`, `throttle:login`, `throttle:register`; khôi phục CSRF cho `/chatbot/message`; thêm `TRUSTED_PROXIES`. |
| **3** | **SePay Webhook Security & Idempotency** | **PASS (Automated Test)** | Bắt buộc token Header / HMAC-SHA256, fail-closed, kiểm tra `transferType=in` & `accountNumber`, chống replay `transaction_code`, lọc secret khỏi log. |
| **4** | **Thanh toán VietQR (Số tiền, Mã đơn)** | **PASS (Automated Test)** | Đủ tiền/dư tiền/thiếu tiền/hết hạn/cọc 40% đều hoạt động đúng; dư tiền được ghi chú đối soát. *(Cần test giao dịch thật trên Staging trước GO Production).* |
| **5** | **Đơn hàng & Tồn kho (Race condition, Hoàn tồn)** | **PASS (Single-DB) / PENDING (MySQL Concurrency)** | Giao dịch nguyên tử với `lockForUpdate` & conditional `decrement`, chống hoàn tồn lặp & tất toán COD có cọc. |
| **6** | **Scheduler, Queue worker, phục hồi** | **PASS (Synchronous) / NEEDS OPS CHECK** | Hiện tại xử lý synchronous với DB retry (`attempts: 3`). Nên bổ sung lịch dọn dẹp `BankPaymentSession` hết hạn định kỳ trên Staging/Production. |
| **7** | **Database integrity, transactions, migrations** | **PASS** | Không thay đổi schema; toàn bộ thao tác test chạy trên SQLite `:memory:`, dữ liệu thật nguyên vẹn. |
| **8** | **Load testing & Hiệu năng** | **PASS (Query Count) / BLOCKED (Multi-user Live Load)** | Đã tối ưu trang chủ từ 167 queries xuống `<= 15` queries; chưa chạy load test đa luồng thật (k6/JMeter) vào server Staging. |
| **9** | **End-to-End luồng mua hàng & Quản trị** | **PASS (Automated Test)** | Toàn bộ 70 test feature/unit (giỏ hàng, mua ngay, checkout, VietQR, COD cọc, admin sale/khách/thống kê/đơn hàng, chatbot) đạt 100%. |
| **10** | **Production Configuration & Security** | **PASS (Templates) / PENDING (Hosting Deploy)** | Đã có `.env.staging.example`, `.env.production.example`, `URL::forceScheme('https')`; cần thực hiện trực tiếp khi deploy Staging/Production. |

> [!NOTE]
> Chi tiết từng bản vá kỹ thuật, bằng chứng test trước/sau và danh sách kiểm tra trước khi deploy Staging vui lòng xem tại [`SECURITY_PATCH_REPORT.md`](file:///c:/laragon/www/haomilk/SECURITY_PATCH_REPORT.md).
