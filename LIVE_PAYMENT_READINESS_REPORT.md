# BÁO CÁO SẴN SÀNG THANH TOÁN THẬT & CHUẨN BỊ SEPAY LIVE TEST — HƯƠNG MÍT

- **Ngày thực hiện:** 2026-11-12
- **Môi trường kiểm thử:** PHP `8.3.36` · Laravel `13.38.0` · MySQL `8.4.3` (Laragon `127.0.0.1:3306`) · Apache `2.4.69` · Cloudflare Tunnel `cloudflared`
- **Cơ sở dữ liệu gốc (`haomilk`):** Toàn bộ dữ liệu cũ được giữ nguyên vẹn (`orders = 7`, `stock_receipts = 6`, `users = 3`, `cart_items = 1`); sau khi phát sinh lỗi thiếu cột `idempotency_key` trên `127.0.0.1:8000`, đã sao lưu DB và chạy bổ sung 2 migration mới bằng `php artisan migrate --no-interaction` (`migrations = 20`, đồng bộ `code_sequences`: `orders = 7`, `stock_receipts = 6`).
- **Cơ sở dữ liệu test live riêng biệt (`haomilk_live_test`):** Sạch (`orders = 0`, `bank_transactions = 0`), đã migrate đầy đủ và seed sẵn sản phẩm test giá nhỏ (`2.000 đ`).

---

## 1. Tóm tắt kết quả các kiểm tra đã PASS

### 1.1. Code Style & Bộ test tự động (chạy sau lần chỉnh sửa code PHP cuối)

| Hạng mục | Lệnh chạy | Kết quả quan sát trực tiếp |
| --- | --- | --- |
| Laravel Pint | `php vendor/bin/pint --format agent` | `{"tool":"pint","result":"passed"}` |
| Toàn bộ PHPUnit | `php artisan test --compact` | `76` tests passed, `443` assertions, `0` failed, `0` skipped (`11.41s`) |
| Suite Ready-to-Live | `php artisan test tests/Feature/LivePaymentReadinessTest.php` | `6` tests passed, `106` assertions (`0.91s`) |
| Laravel Scheduler | `php artisan schedule:list` | `No scheduled tasks have been defined.` |

Các test case trong [LivePaymentReadinessTest.php](file:///c:/laragon/www/haomilk/tests/Feature/LivePaymentReadinessTest.php):
1. `test_unconfirmed_real_bank_inflows_are_persisted_for_admin_reconciliation`
2. `test_overpayment_and_second_transfer_to_paid_session_record_reconciliation_without_second_order`
3. `test_duplicate_and_late_webhooks_remain_idempotent_after_cache_flush_and_restart`
4. `test_checkout_idempotency_and_lock_survive_cache_flush_and_different_cache_drivers`
5. `test_migration_and_code_sequences_handle_legacy_data_gaps_and_over_9999`
6. `test_admin_cod_delivered_ui_flow_and_deposit_history_integrity`

### 1.2. Kiểm thử MySQL 8.4.3 thực tế (Migration dữ liệu cũ & Concurrency)

Kiểm thử trên database tách biệt `haomilk_integration_test` (đã dọn/drop sau khi verify) với dữ liệu mô phỏng hình thái DB cũ (`orders = 7` có gap ID `2, 4..9` ứng `DH0001..DH0007`; `stock_receipts = 6` có gap ID `6` ứng `PN0001..PN0006`):
- **Migration cũ:** `2026_11_12_100000_add_concurrency_sequences_and_status_fixes` (`109.21ms DONE`) và `2026_11_12_121926_create_bank_transactions_and_idempotency_support` (`293.33ms DONE`) chạy thành công trên MySQL `8.4.3`, đồng bộ bảng `code_sequences` về đúng `orders = 7`, `stock_receipts = 6`.
- **Sinh mã không trùng / không tiêu thụ thừa:**
  - `Order::generateOrderCode()` trả về `DH0008`.
  - `StockReceipt::getNextCode()` xem trước `PN0007` mà giữ nguyên `code_sequences.current_number = 6`.
  - `StockReceipt::allocateNextCode()` cấp phát tuần tự `PN0007`, `PN0008`.
  - 2 worker PHP chạy song song đồng thời gọi 5 lần/worker sinh được `10/10` mã duy nhất (`0` va chạm).
- **Khóa đồng bộ & Idempotency bền vững qua `Cache::flush()` và `CACHE_STORE=array`:**
  - `ConcurrencyLock::run` thực thi thành công trên MySQL 8 bằng advisory lock (`GET_LOCK` / `RELEASE_LOCK`) ngay cả khi ép `cache.default = array` + `Cache::flush()`.
  - 2 worker chạy song song `POST /thanh-toan` (đã đăng nhập CSRF thật, cùng `idempotency_key` sau `Cache::flush()`) chỉ tạo đúng `1` phiên `BankPaymentSession` (`PAY-KX3ILL2CKU`, nội dung `HM590199`).
  - 2 worker chạy song song + 1 lần replay sau `Cache::flush()` gọi `POST /api/payment/webhook` tạo đúng `1` đơn hàng `DH0019`, trừ tồn kho đúng `1` lần (`5 -> 4`), ghi đúng `1` dòng `bank_transactions` (`status = matched`).

### 1.3. Hạ tầng cô lập cho SePay Live Test (Apache + Cloudflare Tunnel)

Đã dựng tách biệt hoàn toàn khỏi Apache/DB mặc định của Laragon và giữ trạng thái đang chạy để bạn thực hiện test chuyển khoản thật:
- **Database live test:** `haomilk_live_test` (`live_test_orders = 0`, `live_test_bank_tx = 0`, `live_test_users = 2`, `live_test_products = 3`).
- **Tệp môi trường riêng:** `.env.integration` (đã thêm vào [`.gitignore`](file:///c:/laragon/www/haomilk/.gitignore)):
  - `APP_ENV=integration`, `APP_DEBUG=false`, `APP_URL=http://127.0.0.1:8011`, `DB_DATABASE=haomilk_live_test`
  - Ngân hàng thụ hưởng đọc từ `.env`: `BANK_ID=MB` (`Ngân hàng Quân Đội (MB Bank)`), tên tài khoản `CUA HANG HUONG MIT`, 4 số cuối tài khoản `*2734`, thời hạn QR `10` phút.
  - `PAYMENT_WEBHOOK_TOKEN`: đã cấu hình (`33` ký tự). `PAYMENT_WEBHOOK_HMAC_SECRET`: chưa cấu hình (sử dụng cơ chế xác thực `Authorization: Apikey <token>` chuẩn SePay).
- **Local UI Origin (`http://127.0.0.1:8011`, chỉ `Require local`):**
  - `GET http://127.0.0.1:8011/` -> `200` và hiển thị sản phẩm test `Gói Test VietQR 2.000đ` (xác nhận đang dùng đúng `haomilk_live_test`).
  - `POST http://127.0.0.1:8011/thanh-toan/chuyen-khoan/PAY-TEST/gia-lap-thanh-cong` -> `404` (nút/route giả lập thanh toán đã bị vô hiệu hóa).
- **Webhook-Only Origin (`http://127.0.0.1:8012`) & HTTPS Cloudflare Tunnel:**
  - Tunnel đang hoạt động: `https://picture-cheaper-reasoning-appreciate.trycloudflare.com` -> `http://127.0.0.1:8012`.
  - Kết quả kiểm tra thực tế qua cả `127.0.0.1:8012` và HTTPS công khai `https://picture-cheaper-reasoning-appreciate.trycloudflare.com`:
    - `GET /` -> `403`
    - `GET /admin` -> `403`
    - `POST /webhook/payment` -> `403`
    - `POST /thanh-toan/chuyen-khoan/PAY-TEST/gia-lap-thanh-cong` -> `403`
    - `POST /api/payment/webhook` không kèm token -> `401`
    - `POST /api/payment/webhook` sai token -> `401`
    - `POST /api/payment/webhook` đúng `Authorization: Apikey <PAYMENT_WEBHOOK_TOKEN>` với payload ping `SEPAYTEST` -> `200 {"success":true,"message":"SePay test webhook received successfully."}`, đồng thời `haomilk_live_test` vẫn sạch (`orders = 0`, `bank_transactions = 0`).

---

## 2. Các vấn đề đã phát hiện và khắc phục (Nhiệm vụ 1)

1. **Ghi nhận mọi dòng tiền thực nhận (thiếu tiền, thừa tiền, trễ hạn, đã hủy, chưa khớp mã, chuyển đúp, hết tồn kho):**
   - Tạo bảng sổ cái `bank_transactions` trong migration [2026_11_12_121926_create_bank_transactions_and_idempotency_support.php](file:///c:/laragon/www/haomilk/database/migrations/2026_11_12_121926_create_bank_transactions_and_idempotency_support.php) và model [BankTransaction.php](file:///c:/laragon/www/haomilk/app/Models/BankTransaction.php).
   - Cập nhật [PaymentWebhookController.php](file:///c:/laragon/www/haomilk/app/Http/Controllers/PaymentWebhookController.php) để lưu bền vững mọi giao dịch tiền vào (kể cả khi không tạo đơn) với các trạng thái `matched`, `overpaid`, `underpaid`, `expired_late`, `session_cancelled`, `unmatched_code`, `duplicate_tx`, `out_of_stock`, `replay_blocked`, `error` và cờ `reconciliation_status` (`auto_matched` / `needs_review` / `resolved`).
   - Bổ sung màn hình quản trị đối soát [BankTransactionController.php](file:///c:/laragon/www/haomilk/app/Http/Controllers/Admin/BankTransactionController.php) tại route `admin.bank-transactions.index` và action `admin.bank-transactions.reconcile` (file view [index.blade.php](file:///c:/laragon/www/haomilk/resources/views/admin/bank_transactions/index.blade.php) và mục menu trong [admin.blade.php](file:///c:/laragon/www/haomilk/resources/views/layouts/admin.blade.php)).
2. **An toàn Migration dữ liệu cũ & cấp phát mã duy nhất `DHxxxx` / `PNxxxx`:**
   - Rà soát và sửa [2026_11_12_100000_add_concurrency_sequences_and_status_fixes.php](file:///c:/laragon/www/haomilk/database/migrations/2026_11_12_100000_add_concurrency_sequences_and_status_fixes.php) để nâng `current_number` theo dữ liệu hiện có khi thấp hơn, sửa kiểm tra index tương thích cả SQLite và MySQL (`Schema::hasIndex`).
   - Tách hàm xem trước `CodeSequence::previewNextCode()` ([CodeSequence.php](file:///c:/laragon/www/haomilk/app/Models/CodeSequence.php)) với `StockReceipt::allocateNextCode()` ([StockReceipt.php](file:///c:/laragon/www/haomilk/app/Models/StockReceipt.php)) để mở form tạo phiếu nhập không làm nhảy mất số sequence.
3. **Khắc phục phụ thuộc Cache driver cho `Cache::lock` và Idempotency khi restart / xóa cache:**
   - Thêm helper [ConcurrencyLock.php](file:///c:/laragon/www/haomilk/app/Services/ConcurrencyLock.php) dùng MySQL/MariaDB `GET_LOCK` / `RELEASE_LOCK` ở tầng database (fallback an toàn sang `Cache::lock` trên driver khác), bảo đảm khóa đa tiến trình vẫn hoạt động khi hosting dùng `CACHE_STORE=file` hoặc `array`.
   - Lưu `idempotency_key` xuống DB trên `orders` (`orders_user_idempotency_unique`) và `bank_payment_sessions` (`bps_user_idempotency_unique`) tại [CheckoutController.php](file:///c:/laragon/www/haomilk/app/Http/Controllers/CheckoutController.php), kết hợp `dedup_hash` unique trên `bank_transactions` giúp chống double-submit và chống webhook trùng/muộn bền vững ngay cả sau `cache:clear` hoặc restart server.
4. **Hoàn thiện luồng COD giao thành công & bảo vệ lịch sử tiền cọc:**
   - Cập nhật [Admin/OrderController.php](file:///c:/laragon/www/haomilk/app/Http/Controllers/Admin/OrderController.php) và các view đơn hàng (`admin/orders/{index,show,print}.blade.php`, `account/{orders,order_detail}.blade.php`, `checkout/success.blade.php`, `admin/customers/show.blade.php`): bổ sung tab lọc & nút `Đánh dấu Giao thành công` (`delivered`), timeline 3 bước, hiển thị đúng số tiền cọc 40% qua `$order->deposit_amount` (thay vì nhầm sang `$order->paid_amount`), và không ghi đè bản ghi `Payment` loại `'deposit'` khi Admin chuyển trạng thái thanh toán về `'unpaid'`.

---

## 3. Các hạng mục còn BLOCKED (không thể tự động hóa thay chủ dự án)

1. **Tạo giao dịch chuyển khoản MB Bank bằng tiền thật & thay đổi cấu hình trên SePay Live Dashboard:** Bị chặn theo nguyên tắc an toàn (cần thao tác trực tiếp bởi chủ tài khoản MB Bank / SePay).
2. **URL Cloudflare Quick Tunnel tạm thời (`trycloudflare.com`):** Sinh động cho phiên kiểm thử live hiện tại; nếu tắt hoặc khởi động lại `cloudflared`, URL sẽ đổi và cần cập nhật lại trên SePay Dashboard. Không dùng Quick Tunnel này cho production lâu dài.
3. **HMAC Signature (`PAYMENT_WEBHOOK_HMAC_SECRET`):** Hiện chưa thiết lập trong `.env`, nên webhook sử dụng xác thực token `Authorization: Apikey`. Khi lên production chính thức, có thể giữ `API Key` mạnh (33+ ký tự như hiện tại) hoặc bổ sung thêm `PAYMENT_WEBHOOK_HMAC_SECRET` nếu bật ký chữ ký phía SePay.
4. **Scheduled Jobs trên hosting (`php artisan schedule:list`):** Chưa có cron job tự động định kỳ nào được khai báo trong `routes/console.php`; việc hết hạn QR vẫn an toàn ở tầng webhook/session vì hệ thống kiểm tra trực tiếp `expires_at` và đánh dấu `expired_late` nếu tiền chuyển muộn.

---

## 4. Hướng dẫn chủ dự án tự thực hiện giao dịch test MB Bank thật

> [!IMPORTANT]
> Chỉ dùng đúng các URL test và tài khoản test bên dưới. **Không** đăng nhập hoặc trỏ SePay vào URL khác ngoài URL Webhook HTTPS ở Bước 1.

### Bước 1 — Cấu hình Webhook tạm thời trên SePay Dashboard
1. Đăng nhập trang quản trị **SePay Dashboard** -> mục **Tích hợp / Webhooks** -> **Thêm Webhook** (hoặc sửa webhook test tạm).
2. Điền thông tin:
   - **Tên gợi nhớ:** `Huong Mit Live Test Local`
   - **Sự kiện (Event):** `Có tiền vào` (`In`)
   - **Tài khoản ngân hàng áp dụng:** Tài khoản MB Bank trùng với `.env` (4 số cuối `*2734`).
   - **Webhook URL:**
     ```text
     https://picture-cheaper-reasoning-appreciate.trycloudflare.com/api/payment/webhook
     ```
   - **Kiểu xác thực (Authentication Type):** `API Key`
   - **API Key:** Dán đúng giá trị của biến `PAYMENT_WEBHOOK_TOKEN` trong tệp `.env` trên máy của bạn (lưu ý không chia sẻ token này ra ngoài).
3. Nhấn **Lưu** và bấm **Test Webhook / Ping** trên SePay Dashboard: kỳ vọng nhận `HTTP 200 OK` (SePay ping không ghi đơn ảo vào DB).

### Bước 2 — Tạo đơn VietQR giá trị nhỏ trên môi trường test cô lập
1. Trên máy local, mở trình duyệt vào đúng cổng test cô lập:
   - Trang chủ: `http://127.0.0.1:8011`
2. Đăng nhập tài khoản khách test:
   - **Email:** `khach.livetest@haomilk.test`
   - **Mật khẩu:** `LiveTestUser!2026`
3. Chọn sản phẩm **`Gói Test VietQR 2.000đ`** (số lượng `1`), bấm **Mua ngay** (hoặc thêm giỏ hàng rồi vào thanh toán).
4. Tại màn hình thanh toán:
   - Chọn địa chỉ mặc định (`Khách Live Test - Số 10 Kim Mã`).
   - Chọn phương thức **Chuyển khoản ngân hàng (VietQR)**.
   - Bấm **Đặt hàng / Thanh toán ngay** -> hệ thống hiển thị mã QR với số tiền `2.000 đ` và mã nội dung chuyển khoản dạng **`HMxxxxxx`**.
5. Dùng app **MB Bank** (hoặc ngân hàng bất kỳ hỗ trợ VietQR) **quét đúng mã QR đang hiển thị** (hoặc chuyển `2.000 đ` giữ nguyên chính xác nội dung `HMxxxxxx`).

*(Tùy chọn mở rộng: Nếu muốn test thêm luồng COD có cọc 40%, có thể đặt sản phẩm `Sữa Test Cọc COD 40% (500.000đ)` với phương thức COD -> hệ thống yêu cầu cọc QR đúng `200.000 đ`).*

---

## 5. Cách xác minh số tiền, mã giao dịch, trạng thái đơn và tồn kho

### 5.1. Kiểm tra trên Giao diện (Khách hàng & Admin)
1. **Phía Khách hàng (`http://127.0.0.1:8011`):**
   - Sau khi chuyển khoản thành công và webhook SePay xử lý `200 OK`, đơn hàng mới (khởi đầu từ `DH0001` trên DB sạch `haomilk_live_test`) được tạo và truy cập tại `/thanh-toan/DH0001/thanh-cong`.
   - Kiểm tra mục `/tai-khoan/don-hang`: hiển thị đơn **`DH0001`**, tổng tiền `2.000 đ`, trạng thái thanh toán **Đã thanh toán** (`paid`).
2. **Phía Admin (`http://127.0.0.1:8011/admin`):**
   - Đăng nhập bằng tài khoản Admin test: `admin.livetest@haomilk.test` / `LiveTestAdmin!2026`.
   - Mở **Đơn hàng** (`/admin/orders`): kiểm tra đơn `DH0001`, tổng tiền, bản ghi `Payment` với `transaction_code` từ SePay, tồn kho sản phẩm `Gói Test VietQR 2.000đ` giảm từ `50` xuống `49`.
   - Mở **Đối soát CK / VietQR** (`/admin/bank-transactions`): kiểm tra dòng giao dịch thực nhận từ SePay hiển thị đúng số tiền nhận (`2.000 đ`), chênh lệch (`0 đ`), trạng thái `Khớp đơn hàng` (`matched`), đối soát `Khớp tự động` (`auto_matched`) và gắn đúng `order_id` của `DH0001`.

### 5.2. Kiểm tra trực tiếp trong MySQL `haomilk_live_test` (chỉ đọc)

```powershell
& "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -u root -p -e "
SELECT id, order_code, total_amount, paid_amount, payment_status, order_status FROM haomilk_live_test.orders;
SELECT id, sepay_id, transaction_code, transfer_code, expected_amount, received_amount, status, reconciliation_status, order_id FROM haomilk_live_test.bank_transactions;
SELECT id, order_id, payment_type, amount, status, transaction_code, paid_at FROM haomilk_live_test.payments;
SELECT pv.id, p.name, pv.stock_quantity FROM haomilk_live_test.product_variants pv JOIN haomilk_live_test.products p ON p.id = pv.product_id;
SELECT COUNT(*) AS orig_orders FROM haomilk.orders;
"
```

Kỳ vọng:
- `orders`: 1 dòng `DH0001`, `paid_amount = 2000.00`, `payment_status = paid`.
- `bank_transactions`: 1 dòng khớp `sepay_id`, `transaction_code`, `received_amount = 2000.00`, `status = matched`, `reconciliation_status = auto_matched`.
- `product_variants`: tồn kho `Gói Test VietQR 2.000đ` từ `50` giảm còn `49`.
- `orig_orders`: vẫn đúng `7` (DB gốc `haomilk` không thay đổi).

---

## 6. Cách hoàn nguyên cấu hình test và đóng Tunnel sau khi test xong

Sau khi hoàn tất kiểm thử giao dịch thật:

1. **Trên SePay Dashboard:** Xóa hoặc tắt Webhook trỏ tới `https://picture-cheaper-reasoning-appreciate.trycloudflare.com/api/payment/webhook` (tránh SePay gửi retry vào tunnel đã đóng).
2. **Đóng Cloudflare Tunnel và Apache test (`8011`/`8012`), xóa `.env.integration` và database `haomilk_live_test`** bằng PowerShell:

```powershell
# 1. Dừng riêng cloudflared và tiến trình httpd chạy config integration
Get-CimInstance Win32_Process |
    Where-Object { $_.CommandLine -like '*httpd-integration.conf*' -or $_.Name -eq 'cloudflared.exe' } |
    ForEach-Object { Stop-Process -Id $_.ProcessId -Force }

# 2. Xóa tệp môi trường integration tạm
Remove-Item "C:\laragon\www\haomilk\.env.integration" -ErrorAction SilentlyContinue

# 3. Xóa database test live (giữ nguyên hoàn toàn database gốc haomilk)
& "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -u root -p -e "DROP DATABASE IF EXISTS haomilk_live_test; SELECT COUNT(*) AS haomilk_orders_intact FROM haomilk.orders;"
```

*(Lưu ý: Nếu cần chạy lại migrate trên database gốc hoặc production thực tế khi deploy chính thức, chạy `php artisan migrate --force` trên đúng môi trường đích sau khi sao lưu DB.)*
