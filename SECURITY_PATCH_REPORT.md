# BÁO CÁO VÁ LỖ HỔNG BẢO MẬT & SẴN SÀNG STAGING (SECURITY PATCH REPORT)

**Dự án:** Website Thương mại Điện tử Hương Mít (`haomilk`)  
**Nền tảng:** Laravel 13 | PHP 8.3 | MySQL (Laragon Local) / SQLite `:memory:` (PHPUnit)  
**Ngày hoàn thành bản vá:** 12/11/2026  
**Phạm vi tài liệu:** Chi tiết khắc phục P0/P1/P2 từ [`PRE_PRODUCTION_TEST_REPORT.md`](file:///c:/laragon/www/haomilk/PRE_PRODUCTION_TEST_REPORT.md) và đánh giá điều kiện triển khai **Staging**.

---

## 1. TÓM TẮT ĐIỀU HÀNH (EXECUTIVE SUMMARY)

| Hạng mục | Kết quả |
| :--- | :--- |
| **Kết quả Test Suite toàn hệ thống (`php vendor/bin/phpunit`)** | **70 tests, 70 PASS, 0 FAIL, 0 SKIP (337 assertions)** |
| **Bộ test Audit & Security (`PreProductionAuditTest`)** | **24 tests, 24 PASS, 0 FAIL (128 assertions)** |
| **Định dạng code chuẩn (`Laravel Pint`)** | **PASS** toàn bộ 20 file PHP đã chỉnh sửa/cập nhật |
| **Ẩn route giả lập trên Staging & Production (`route:list`)** | **PASS** — `checkout.bank-payment.simulate` không tồn tại ở `--env=staging` và `--env=production` |
| **Ảnh hưởng cơ sở dữ liệu thật** | **Không có** (0 migration mới, 0 reset/chỉnh sửa dữ liệu trên DB local `haomilk`) |
| **Đánh giá triển khai** | 🟡 **ĐỦ ĐIỀU KIỆN DEPLOY STAGING** *(Chưa đủ điều kiện Production GO cho đến khi hoàn tất Checklist mục 7 trên Staging thật)* |

---

## 2. CHI TIẾT KHẮC PHỤC LỖ HỔNG & TỐI ƯU HỆ THỐNG

### 2.1. Nhóm CRITICAL (P0)

#### [SEC-01] Sửa lỗi bỏ qua xác thực SePay Webhook (Fail-Closed Authentication)
- **Nguyên nhân gốc trong mã nguồn:**
  1. [`PaymentWebhookController::handle()`](file:///c:/laragon/www/haomilk/app/Http/Controllers/PaymentWebhookController.php) sử dụng biểu thức `if ($token && $token !== $configuredToken)` nên request không mang token (`$token === null`) lọt qua bước xác thực.
  2. Chấp nhận token qua URL query string/body (`$request->input('token')`).
  3. Ghi log toàn bộ `$request->all()`, có nguy cơ rò rỉ dữ liệu nhạy cảm.
  4. [`config/bank.php`](file:///c:/laragon/www/haomilk/config/bank.php) đặt giá trị fallback cứng cho `webhook_token`.
- **Cách khắc phục:**
  - Loại bỏ giá trị token mặc định cứng trong [`config/bank.php`](file:///c:/laragon/www/haomilk/config/bank.php) và bổ sung cấu hình `webhook_hmac_secret`.
  - Xây dựng [`PaymentWebhookController::authenticateWebhook()`](file:///c:/laragon/www/haomilk/app/Http/Controllers/PaymentWebhookController.php):
    - **Fail-closed:** Nếu cả `bank.webhook_token` và `bank.webhook_hmac_secret` đều rỗng -> trả về **HTTP 500** và ghi log `critical`, tuyệt đối không bỏ qua xác thực (kể cả gói ping `SEPAYTEST`).
    - Chỉ đọc API Key từ HTTP Header (`Authorization: Apikey/Bearer ...`, `X-Api-Key`, `secure-token`); bỏ qua hoàn toàn `?token=` trên query string hoặc body.
    - So sánh secret bằng `hash_equals()` để chống timing attack; trả về **HTTP 401** khi thiếu hoặc sai token.
    - Hỗ trợ xác thực chữ ký **HMAC-SHA256** trên raw request body (`$request->getContent()`) thông qua header `X-Sepay-Signature` / `X-Signature` / `X-Hmac-Signature` (hỗ trợ hex và base64) nếu `PAYMENT_WEBHOOK_HMAC_SECRET` được cấu hình.
  - Chuẩn hóa log qua `sanitizeLogContext()` — chỉ ghi metadata an toàn (4 số cuối tài khoản, mã tham chiếu, số tiền), không ghi Authorization header hoặc token.
- **Kiểm chứng test tự động (`PreProductionAuditTest`):**
  - `test_sec_01_webhook_rejects_missing_authorization_header` (401)
  - `test_sec_01_webhook_rejects_invalid_token_and_query_string_token` (401)
  - `test_sec_01_webhook_fails_closed_when_secrets_not_configured` (500)
  - `test_sec_01_webhook_hmac_sha256_accepts_valid_and_rejects_tampered_payload` (401 với payload bị sửa, 200 với chữ ký đúng)
  - `test_sec_01_webhook_does_not_log_sensitive_auth_tokens`

#### [SEC-02] Vô hiệu hóa hoàn toàn Endpoint giả lập thanh toán trên Staging & Production
- **Nguyên nhân gốc trong mã nguồn:**
  - Route `checkout.bank-payment.simulate` được đăng ký vô điều kiện trong [`routes/web.php`](file:///c:/laragon/www/haomilk/routes/web.php).
  - [`CheckoutController::simulateBankPaymentSuccess()`](file:///c:/laragon/www/haomilk/app/Http/Controllers/CheckoutController.php) không kiểm tra môi trường.
  - Giao diện [`resources/views/checkout/bank_payment.blade.php`](file:///c:/laragon/www/haomilk/resources/views/checkout/bank_payment.blade.php) dùng `@if(config('app.debug', false) || true)` khiến form giả lập luôn hiển thị và sẽ gây lỗi `RouteNotFoundException` nếu bỏ route mà không bảo vệ view.
- **Cách khắc phục:**
  - Trong [`routes/web.php`](file:///c:/laragon/www/haomilk/routes/web.php): Chỉ đăng ký route `gia-lap-thanh-cong` khi `app()->environment(['local', 'testing'])`.
  - Trong [`CheckoutController::simulateBankPaymentSuccess()`](file:///c:/laragon/www/haomilk/app/Http/Controllers/CheckoutController.php): Kiểm tra cả `app()->environment(['local', 'testing'])` và `config('app.env')` — gọi `abort(404)` cho mọi môi trường khác (staging/production), bất kể `APP_DEBUG=true` hay `false`.
  - Trong [`bank_payment.blade.php`](file:///c:/laragon/www/haomilk/resources/views/checkout/bank_payment.blade.php): Bọc khối giả lập bằng `@if(app()->environment(['local', 'testing']) && \Illuminate\Support\Facades\Route::has('checkout.bank-payment.simulate'))`.
- **Kiểm chứng:**
  - `php artisan route:list --env=production --path=thanh-toan` và `--env=staging`: Chỉ hiển thị 6 route thanh toán, không có route giả lập.
  - `test_sec_02_simulate_endpoint_blocked_in_production_and_staging_even_with_debug_true` (404).
  - `test_sec_02_valid_sepay_webhook_still_works_when_app_env_is_production` (200).

---

### 2.2. Nhóm HIGH (P1)

#### [SEC-06] Chặn leo thang đặc quyền Admin trong `Admin\CustomerController`
- **Nguyên nhân gốc:** `show()`, `toggleLock()`, `update()`, `resetPassword()` trong [`CustomerController.php`](file:///c:/laragon/www/haomilk/app/Http/Controllers/Admin/CustomerController.php) nhận `User $customer` qua Route Model Binding mà không kiểm tra vai trò `$customer->isCustomer()`.
- **Cách khắc phục:**
  - Thêm hàm `ensureCustomer(User $customer)` gọi `abort(403, ...)` nếu `! $customer->isCustomer()` và áp dụng cho cả `show()`, `toggleLock()` (sau kiểm tra tự khóa chính mình để giữ UX redirect lỗi hợp lệ), `update()`, `resetPassword()`.
  - Bổ sung phản hồi JSON `401`/`403` cho API requests từ user chưa đăng nhập / không phải Admin trong [`AdminMiddleware.php`](file:///c:/laragon/www/haomilk/app/Http/Middleware/AdminMiddleware.php) (giữ nguyên redirect về `home`/`login` đối với request web thông thường).
- **Kiểm chứng:** `test_sec_06_admin_customer_controller_blocks_mutations_on_admin_accounts` + toàn bộ `AdminCustomerTest`.

#### [SEC-07] Xác minh loại giao dịch, tài khoản thụ hưởng & chống Replay trên Webhook SePay
- **Nguyên nhân gốc:** [`PaymentWebhookController::processTransaction()`](file:///c:/laragon/www/haomilk/app/Http/Controllers/PaymentWebhookController.php) không kiểm tra `transferType`, không kiểm tra `accountNumber`, và nhánh Casso `data[]` cũng bỏ qua các kiểm tra này; chưa chặn tái sử dụng `referenceCode` giữa hai phiên khác nhau.
- **Cách khắc phục:**
  - Bắt buộc `transferType === 'in'` (không phân biệt hoa thường); từ chối `'out'` hoặc payload thiếu `transferType` với **HTTP 422**.
  - Chuẩn hóa và đối chiếu `accountNumber` từ webhook với `config('bank.account_number')` bằng `hash_equals()`; fail-closed (**HTTP 500**) nếu chưa cấu hình số tài khoản cửa hàng; từ chối (**HTTP 422**) và log cảnh báo đối soát nếu thiếu hoặc sai tài khoản nhận.
  - Kiểm tra `amount > 0`; từ chối giao dịch thiếu tiền hoặc phiên hết hạn với payload `{success: false}` kèm cảnh báo log đối soát.
  - Trong [`BankPaymentSession::completePayment()`](file:///c:/laragon/www/haomilk/app/Models/BankPaymentSession.php):
    - Kiểm tra trong transaction `lockForUpdate()` rằng `transaction_code` bên ngoài chưa từng gắn với đơn `paid` khác (ngăn replay attack liên phiên, trả về **HTTP 409**); đồng thời giữ nguyên cơ chế **idempotent 200 OK** khi SePay gửi lặp lại cho chính phiên đã thanh toán.
    - Ghi chú rõ số tiền khách chuyển dư vào `Payment::note` và log `warning` để nhân viên đối soát hoàn tiền khi cần.
- **Kiểm chứng:** `test_pay_01_duplicate_and_replay_webhook_are_idempotent_and_replay_blocked`, `test_pay_02_webhook_rejects_underpayment`, `test_pay_03_webhook_rejects_expired_session`, `test_sec_07_webhook_verifies_destination_account_and_incoming_transfer_type`, `test_sec_07_webhook_handles_overpayment_with_reconciliation_note`.

#### [RATE-01] Bảo vệ Endpoint Chatbot AI, khôi phục CSRF & ẩn Gemini API Key khỏi URL
- **Nguyên nhân gốc + phát hiện bổ sung khi review:**
  1. `/chatbot/message` không có rate limiter.
  2. `'chatbot/*'` bị đưa vào danh sách miễn trừ CSRF trong [`bootstrap/app.php`](file:///c:/laragon/www/haomilk/bootstrap/app.php) mặc dù giao diện `chatbot.blade.php` đã gửi header `X-CSRF-TOKEN`.
  3. [`GeminiService.php`](file:///c:/laragon/www/haomilk/app/Services/GeminiService.php) truyền API Key qua query string `?key=...` (dễ lộ key trong HTTP access log/proxy log) và tắt kiểm tra chứng chỉ SSL (`'verify' => false`).
- **Cách khắc phục:**
  - Xóa `'chatbot/*'` khỏi danh sách miễn CSRF trong [`bootstrap/app.php`](file:///c:/laragon/www/haomilk/bootstrap/app.php).
  - Định nghĩa `RateLimiter::for('chatbot', ...)` trong [`AppServiceProvider.php`](file:///c:/laragon/www/haomilk/app/Providers/AppServiceProvider.php) với giới hạn **12 request/phút** và **120 request/ngày** theo `user_id` hoặc `ip()`, gắn middleware `throttle:chatbot` cho route `chatbot.message` trong [`routes/web.php`](file:///c:/laragon/www/haomilk/routes/web.php).
  - Chuyển Gemini API Key sang header `x-goog-api-key` và bật `'verify' => true` mặc định trên môi trường `staging`/`production` (cấu hình cache-safe qua `config('services.gemini.verify_ssl')` trong [`config/services.php`](file:///c:/laragon/www/haomilk/config/services.php)).
- **Kiểm chứng:** `test_rate_01_chatbot_rate_limit_blocks_excess_and_skips_gemini_and_uses_header_key`, `test_rate_01_chatbot_enforces_csrf_outside_unit_testing_environment` + toàn bộ `ChatbotTest`.

#### [RACE-01] Khóa giao dịch nguyên tử cho Checkout & Admin Order Status
- **Nguyên nhân gốc + phát hiện bổ sung:**
  1. [`CheckoutController::process()`](file:///c:/laragon/www/haomilk/app/Http/Controllers/CheckoutController.php) thực hiện kiểm tra tồn kho và `lockForUpdate()` **bên ngoài** `DB::transaction()`.
  2. [`Admin\OrderController::updateStatus()`](file:///c:/laragon/www/haomilk/app/Http/Controllers/Admin/OrderController.php) đọc `$oldStatus = $order->order_status` **bên ngoài** `DB::transaction()` và không khóa `Order`, cho phép hai request hủy đơn đồng thời hoàn tồn kho 2 lần; khôi phục đơn từ `cancelled` không kiểm tra đủ tồn kho.
- **Cách khắc phục:**
  - Bọc toàn bộ phần kiểm tra tồn kho, tạo địa chỉ, tạo `BankPaymentSession` hoặc tạo đơn COD trong **một `DB::transaction(..., 3)` duy nhất**; khóa biến thể theo thứ tự `id` tăng dần để tránh deadlock.
  - Dùng cập nhật có điều kiện nguyên tử (`ProductVariant::where('id', ...)->where('stock_quantity', '>=', $qty)->decrement(...)`, kiểm tra số dòng bị ảnh hưởng `=== 1`) ở cả [`CheckoutController`](file:///c:/laragon/www/haomilk/app/Http/Controllers/CheckoutController.php) và [`BankPaymentSession::completePayment()`](file:///c:/laragon/www/haomilk/app/Models/BankPaymentSession.php), kèm kiểm tra quota `SaleCampaignItem` có điều kiện.
  - Trong [`Admin\OrderController::updateStatus()`](file:///c:/laragon/www/haomilk/app/Http/Controllers/Admin/OrderController.php): Truy vấn lại `Order::with('items')->lockForUpdate()->findOrFail($order->id)` bên trong `DB::transaction(..., 3)` trước khi đọc `$oldStatus`, khóa biến thể theo thứ tự ID và dùng atomic `decrement` khi khôi phục đơn đã hủy.
- **Kiểm chứng:** `test_ord_01_and_race_01_admin_cancelling_twice_restores_stock_only_once`, `test_race_01_checkout_blocks_overselling_when_stock_depleted_before_commit`. *(Lưu ý: Kiểm thử tranh chấp đa luồng thực tế trên MySQL cần chạy bổ sung ở môi trường Staging).*

---

### 2.3. Nhóm MEDIUM (P2)

#### [ORD-02] Tất toán tự động đơn COD có đặt cọc 40% khi giao thành công
- **Cách khắc phục:** Mở rộng điều kiện trong [`Admin\OrderController::updateStatus()`](file:///c:/laragon/www/haomilk/app/Http/Controllers/Admin/OrderController.php) cho `in_array($lockedOrder->payment_status, ['unpaid', 'deposit_paid'], true)`: đưa `payment_status = 'paid'`, `paid_amount = total_amount`, `remaining_amount = 0`, cập nhật payment `pending` hoặc tự động ghi nhận bản ghi `Payment` (`payment_type = 'final'`, `payment_method = 'cod'`, `status = 'paid'`) cho phần 60% thu hộ.
- **Kiểm chứng:** `test_ord_02_delivered_cod_with_deposit_clears_remaining_balance`.

#### [RATE-02] Rate Limiter cho Đăng nhập & Đăng ký + TrustProxies có kiểm soát
- **Cách khắc phục:**
  - Trong [`AppServiceProvider.php`](file:///c:/laragon/www/haomilk/app/Providers/AppServiceProvider.php): Cấu hình `RateLimiter::for('login', ...)` giới hạn kép (**5 lần/phút theo cặp `email|ip`** và **10 lần/phút theo `ip`** để chặn dò quét nhiều email từ cùng IP) và `RateLimiter::for('register', ...)` (**5 lần/phút theo `ip`**).
  - Gắn `throttle:login` và `throttle:register` trong [`routes/web.php`](file:///c:/laragon/www/haomilk/routes/web.php).
  - Trong [`AuthController::login()`](file:///c:/laragon/www/haomilk/app/Http/Controllers/Auth/AuthController.php): Làm tròn nguyên dương số phút còn lại khi tài khoản bị khóa DB.
  - Trong [`bootstrap/app.php`](file:///c:/laragon/www/haomilk/bootstrap/app.php): Hỗ trợ whitelist `TRUSTED_PROXIES` từ `.env` (chỉ tin cậy `X-Forwarded-For` khi cấu hình IP proxy hợp lệ, không mặc định tin cậy mọi header giả mạo).
- **Kiểm chứng:** `test_sec_05_account_lockout_and_ip_rate_limit_work_together`, `test_rate_02_login_blocks_email_spraying_from_same_ip`.

#### [PERF-01] Khắc phục N+1 Query trên `Product` và `ProductVariant`
- **Nguyên nhân phát hiện thực tế:** Cả [`ProductVariant::getActiveSaleItemAttribute()`](file:///c:/laragon/www/haomilk/app/Models/ProductVariant.php) và [`Product::getActiveSaleItemAttribute()`](file:///c:/laragon/www/haomilk/app/Models/Product.php) đều thực hiện query lặp mỗi lần truy cập (6 lần/sản phẩm trong `home.blade.php`). Ngoài ra [`Product::getPrimaryImageUrlAttribute()`](file:///c:/laragon/www/haomilk/app/Models/Product.php) không kiểm tra `$this->images->isEmpty()` khi `images` đã được eager load rỗng, gây thêm 1 query/sản phẩm không có ảnh! Tổng cộng **167 queries** trên trang chủ với 12 sản phẩm mẫu.
- **Cách khắc phục:**
  - Thêm relation `activeSaleItems()` trên [`Product`](file:///c:/laragon/www/haomilk/app/Models/Product.php) và [`ProductVariant`](file:///c:/laragon/www/haomilk/app/Models/ProductVariant.php), kết hợp memoize qua `$this->setRelation('activeSaleItems', ...)` ngay trong accessor.
  - Sửa `Product::getPrimaryImageUrlAttribute()` trả về `null` ngay khi relation `images` đã được load và rỗng.
  - Bổ sung eager loading `activeSaleItems.campaign` và `variants.activeSaleItems.campaign` trong [`HomeController`](file:///c:/laragon/www/haomilk/app/Http/Controllers/HomeController.php), [`CartController`](file:///c:/laragon/www/haomilk/app/Http/Controllers/CartController.php), [`CheckoutController`](file:///c:/laragon/www/haomilk/app/Http/Controllers/CheckoutController.php), [`ChatbotService`](file:///c:/laragon/www/haomilk/app/Services/ChatbotService.php).
- **Kiểm chứng:** `test_perf_01_home_page_eliminates_variant_active_sale_item_n_plus_1_queries` xác nhận số query trang chủ giảm từ **167** xuống còn **<= 15 queries**.

#### [CONF-01] Chuẩn hóa cấu hình Staging / Production & Ép HTTPS
- **Cách khắc phục:**
  - Trong [`AppServiceProvider::boot()`](file:///c:/laragon/www/haomilk/app/Providers/AppServiceProvider.php): Tự động gọi `URL::forceScheme('https')` khi chạy trên môi trường `staging` hoặc `production`.
  - Xóa token mẫu khỏi [`.env.example`](file:///c:/laragon/www/haomilk/.env.example) và tạo mới [`.env.staging.example`](file:///c:/laragon/www/haomilk/.env.staging.example) & [`.env.production.example`](file:///c:/laragon/www/haomilk/.env.production.example) với thiết lập cứng: `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `LOG_CHANNEL=daily`, `LOG_LEVEL=info`/`warning`, `GEMINI_VERIFY_SSL=true`, `TRUSTED_PROXIES=127.0.0.1` và các trường secret để trống. *(Không sửa đổi `.env` local hay dữ liệu thật).*
- **Kiểm chứng:** `test_conf_01_staging_and_production_env_examples_are_hardened`.

---

## 3. CẬP NHẬT BỘ TEST CŨ THEO NGHIỆP VỤ BẢO MẬT MỚI

Tuân thủ nguyên tắc không làm sai lệch nghiệp vụ, hai test cũ sau đây đã được cập nhật payload do trước đó gửi webhook thiếu xác thực/thiếu trường bắt buộc của SePay (mâu thuẫn trực tiếp với bản vá `SEC-01` và `SEC-07`):
1. [`tests/Feature/BankPaymentCheckoutTest.php`](file:///c:/laragon/www/haomilk/tests/Feature/BankPaymentCheckoutTest.php):
   - `setUp()`: Gán cấu hình test cố định (`Config::set`) cho `bank.webhook_token` và `bank.account_number` để không phụ thuộc `.env` cục bộ.
   - `test_payment_webhook_matches_syntax_and_completes_order()` & `test_payment_webhook_rejects_insufficient_amount()`: Bổ sung header `Authorization: Apikey ...` và payload `transferType: 'in'`, `accountNumber: '0988888888'`.
2. [`tests/Feature/PreProductionAuditTest.php`](file:///c:/laragon/www/haomilk/tests/Feature/PreProductionAuditTest.php):
   - `test_sec_05_account_lockout_and_ip_rate_limit_work_together()`: Cập nhật kiểm tra cả lớp 1 (HTTP 429 ngay sau 5 lần sai trong 1 phút) và lớp 2 (dùng `$this->travel(65)->seconds()` để kiểm tra khóa DB 15 phút vẫn chặn khi qua cửa sổ rate limit 1 phút).

---

## 4. KẾT QUẢ CHẠY KIỂM THỬ THỰC TẾ (VERIFICATION RESULTS)

- **Kiểm thử toàn bộ PHPUnit Suite (`php vendor/bin/phpunit`):**
  - Tổng cộng: **70 tests**, **70 PASS**, **0 FAIL**, **0 SKIP** (**337 assertions**, thời gian ~8.84s trên SQLite `:memory:`).
- **Kiểm thử chuyên sâu Audit/Security (`php vendor/bin/phpunit tests/Feature/PreProductionAuditTest.php`):**
  - Tổng cộng: **24 tests**, **24 PASS**, **0 FAIL** (**128 assertions**, thời gian ~4.54s).
- **Kiểm chuẩn định dạng (`php vendor/bin/pint ... --format agent`):**
  - **PASS** 100% file PHP liên quan.
- **Kiểm tra Route table theo môi trường:**
  - `php artisan route:list --env=production --path=thanh-toan` -> 6 routes (không có `gia-lap-thanh-cong`).
  - `php artisan route:list --env=staging --path=thanh-toan` -> 6 routes (không có `gia-lap-thanh-cong`).

---

## 5. RỦI RO VẬN HÀNH CÒN LẠI (REMAINING OPERATIONAL RISKS)

1. **Chưa kiểm thử tranh chấp khóa (Concurrency Stress Test) trên MySQL 8 InnoDB thật:** SQLite `:memory:` chỉ kiểm chứng tính đúng đắn logic tuần tự/rollback và câu lệnh SQL điều kiện `decrement`, không mô phỏng được lock contention thật dưới hàng chục kết nối đồng thời trên MySQL.
2. **Chưa có Scheduler dọn phiên thanh toán hết hạn:** [`routes/console.php`](file:///c:/laragon/www/haomilk/routes/console.php) chưa cấu hình cron định kỳ dọn bảng `bank_payment_sessions` cũ; hiện trạng thái `expired` được đánh dấu tự động mỗi khi user poll hoặc webhook chạm phiên, hoàn toàn an toàn về mặt nghiệp vụ nhưng về lâu dài nên thêm lệnh dọn dẹp theo ngày.
3. **Mật khẩu/Token phát triển cũ đã từng nằm trong lịch sử local (`.env` / `.env.example`):** Bắt buộc tạo `PAYMENT_WEBHOOK_TOKEN`, `PAYMENT_WEBHOOK_HMAC_SECRET`, `APP_KEY` và API Key hoàn toàn mới khi dựng server Staging và Production.

---

## 6. CHECKLIST KIỂM THỬ THỦ CÔNG TRÊN STAGING TRƯỚC KHI PRODUCTION GO

Trước khi bật Production cho người dùng thật, đội ngũ vận hành/QA cần lần lượt hoàn tất các bước sau trực tiếp trên server **Staging**:

1. **Cấu hình môi trường & Quyền hệ thống:**
   - [ ] Tạo `.env` trên Staging từ `.env.staging.example`, sinh `APP_KEY` mới (`php artisan key:generate`) và nhập bí mật mới (DB, SePay API Key + HMAC Secret mới, Gemini API Key).
   - [ ] Kiểm tra `APP_ENV=staging`, `APP_DEBUG=false`, `APP_URL=https://...`, `SESSION_SECURE_COOKIE=true`, `GEMINI_VERIFY_SSL=true`, `TRUSTED_PROXIES` đúng IP của Nginx/Cloudflare proxy.
   - [ ] Kiểm tra quyền ghi `storage/` và `bootstrap/cache/`, chạy `npm run build`, `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`.
2. **Kiểm tra bảo mật URL công khai:**
   - [ ] Mở trang chuyển khoản VietQR -> xác nhận **không hiển thị** nút "Giả lập chuyển khoản thành công".
   - [ ] Gửi request `POST /thanh-toan/chuyen-khoan/{code}/gia-lap-thanh-cong` -> xác nhận trả về **HTTP 404**.
   - [ ] Gửi request `POST /api/payment/webhook` không có header `Authorization` hoặc sai token -> xác nhận trả về **HTTP 401**.
   - [ ] Gửi request `POST /chatbot/message` từ trình duyệt -> xác nhận hoạt động bình thường với CSRF token và nhận **HTTP 429** từ tin nhắn thứ 13/phút.
3. **Tích hợp SePay thật qua HTTPS:**
   - [ ] Cấu hình Webhook URL Staging HTTPS trên dashboard SePay (Bật **API Key** hoặc **HMAC-SHA256** khớp với `.env` Staging; chọn **Chỉ tiền vào**).
   - [ ] Bấm **"Gửi thử"** trên dashboard SePay -> xác nhận nhận **HTTP 200 OK**.
   - [ ] Thử đặt 1 đơn hàng trị giá nhỏ (hoặc đơn COD > 400k có cọc 40%), chuyển khoản thật qua mã VietQR -> xác nhận đơn được tạo ở trạng thái `pending`, tồn kho trừ đúng 1 lần, admin đánh dấu `delivered` thì đơn tự động chuyển `paid` và `remaining_amount = 0`.
4. **Kiểm thử đồng thời & Sao lưu phục hồi (Backup/Restore):**
   - [ ] Chạy kiểm thử đồng thời (k6 / ApacheBench ~50–100 concurrent users) vào trang chủ và luồng đặt hàng COD sản phẩm cuối cùng trên MySQL Staging -> xác nhận `stock_quantity` không bao giờ âm và không phát sinh HTTP 500 chưa xử lý.
   - [ ] Thực hiện sao lưu tự động MySQL (`mysqldump`) và **thử khôi phục (restore)** sang DB kiểm thử để xác minh tính toàn vẹn dữ liệu trước khi GO-LIVE Production.

