# BÁO CÁO KIỂM THỬ TÍCH HỢP THỰC TẾ: SEPAY WEBHOOK, MYSQL 8 CONCURRENCY & CLOUDFLARE TUNNEL — HƯƠNG MÍT

- **Ngày thực hiện:** 2026-11-12
- **Môi trường kiểm thử:** Laragon Windows, PHP `8.3.36` (`mod_php` ZTS `apache2handler`), Apache `2.4.69` (`mpm_winnt`, `ThreadsPerChild 200`), MySQL `8.4.3` (`29/29` bảng `Engine=InnoDB`), `cloudflared` `2026.10.7`
- **Database kiểm thử cô lập:** `haomilk_integration_test` (DB local thật `haomilk` được giữ nguyên vẹn: `orders=7`, `migrations=18`, chưa chạy migration mới)
- **Trạng thái `.env.integration`:** Đã xóa khỏi thư mục gốc dự án sau khi kiểm thử; toàn bộ tiến trình daemon tạm (Apache, MySQL 8, Cloudflare Tunnel) đã tắt sạch (`0` tiến trình nền còn chạy).

---

## 1. Tổng Quan Kết Quả Kiểm Thử

| Nhóm kiểm thử | Tổng số | PASS | FAIL | BLOCKED / NOT RUN | Ghi chú |
| --- | ---: | ---: | ---: | ---: | --- |
| **MySQL 8 Concurrency (`STOCK-01`..`STOCK-12`, `INV-01`)** | 13 | 13 | 0 | 0 | Chạy thật qua `curl_multi` tới Apache multi-threaded + MySQL 8.4.3 InnoDB |
| **SePay Webhook Integration (`WH-01`..`WH-15`)** | 15 | 15 | 0 | 0 | Payload giả lập hoàn toàn với test token/HMAC riêng; 0 giao dịch ngân hàng thật |
| **COD Có Cọc 40% (`COD DEPOSIT`)** | 1 | 1 | 0 | 0 | Xác minh `pending`, chi tiết, phiếu in và tất toán 60% khi `delivered` |
| **Proxy Rate Limit, Queue & Rollback (`PROXY-01`, `QUEUE-01`)** | 2 | 2 | 0 | 0 | Xác minh `X-Forwarded-For`, rollback DB, `queue:work --once`, `queue:retry` |
| **Cô lập Webhook Origin & Cloudflare Tunnel (`TUNNEL-LOCAL-8012`, `TUNNEL-CF-01`)** | 2 | 2 | 0 | 0 | Quick Tunnel HTTPS tạm thời tới `127.0.0.1:8012` (`APP_ENV=integration`) |
| **Live SePay Dashboard Callback (tiền thật / webhook thật)** | 1 | 0 | 0 | 1 | **NOT RUN / BLOCKED có chủ đích:** giữ nguyên cấu hình & bí mật production, không tự nối Live SePay hay chuyển tiền thật |
| **PHPUnit Regression Suite (`php artisan test --compact`)** | 70 | 70 | 0 | 0 | Chạy lại sau lần format Pint cuối cùng (`337 assertions`, `6688 ms`) |

> **Lưu ý về thứ tự chạy:** Toàn bộ kịch bản MySQL 8 Concurrency (`STOCK-01`..`12`, `INV-01`), `WH-01`..`15`, `COD DEPOSIT`, `PROXY-01`, `QUEUE-01` và Cloudflare Tunnel chạy trước bước chuẩn hóa khoảng trắng Laravel Pint trên 3 file (`config/trustedproxy.php`, `database/migrations/2026_11_12_100000_add_concurrency_sequences_and_status_fixes.php`, `app/Models/CodeSequence.php`). Sau bước Pint, đã chạy lại toàn bộ `php artisan test --compact` (`70 passed / 337 assertions`).

---

## 2. Môi Trường Cô Lập & Nguyên Tắc An Toàn Đã Tuân Thủ

1. **Cô lập Database:** Toàn bộ test tích hợp chạy trên database riêng `haomilk_integration_test` (`29/29` bảng `InnoDB`). Kiểm tra read-only trên DB `haomilk` xác nhận nguyên trạng (`orders=7`, `migrations=18`, bảng `code_sequences` chưa tồn tại `0`).
2. **Cô lập HTTP / VirtualHost (`httpd-integration.conf`, `APP_ENV=integration`):**
   - `http://127.0.0.1:8011`: VirtualHost nội bộ (`Require local`) phục vụ test luồng checkout, admin và người dùng đồng thời trên `ThreadsPerChild 200`. Route giả lập thanh toán local trả về `404`.
   - `http://127.0.0.1:8012`: VirtualHost **chỉ nhận duy nhất `POST /api/payment/webhook`**. Toàn bộ đường dẫn khác (`GET /`, `/admin`, `/dang-nhap`, `/webhook/payment`, `GET /api/payment/webhook`, `/thanh-toan/chuyen-khoan/{code}/gia-lap-thanh-cong`) bị `mod_rewrite` trả `403 Forbidden`.
3. **Bí mật & Ngân hàng:** Sử dụng tài khoản nhận test giả lập (`BANK_ACCOUNT_NO=0988888888`) và secret kiểm thử riêng trong `.env.integration`; không chỉnh sửa `.env` gốc, không lộ API Key/mật khẩu DB, không gửi traffic đến SePay/MB Bank thật.

---

## 3. Lỗi Mới Phát Hiện Trong Kiểm Thử Thật & Bản Vá Đã Áp Dụng

Quá trình chạy Phase 1 (trước vá) và Phase 2 (trên `mod_php` ZTS + MySQL 8) phát hiện **8 lỗi thực tế** mà SQLite `:memory:` trước đây không tái hiện được:

1. **Race condition nạp `.env` trên Apache `mod_php` Windows ZTS (`mpm_winnt`):**
   - **Hiện tượng:** Khi 25–50 thread đồng thời gọi `GET /` sau `POST /dang-nhap`, một số request trả `HTTP 500` với `MissingAppKeyException` hoặc rơi về `Connection: sqlite` (`no such table: products`) dù `APP_ENV=integration` vẫn đúng.
   - **Nguyên nhân:** `Illuminate\Support\Env` mặc định bật `PutenvAdapter`. Trong `mod_php` Windows ZTS (1 tiến trình `httpd.exe` chứa 200 worker thread), `putenv()`/`getenv()` dùng chung CRT `_environ` toàn tiến trình và không thread-safe giữa các request đồng thời.
   - **Bản vá:** Gọi `Env::disablePutenv()` khi `PHP_SAPI === 'apache2handler' && defined('PHP_ZTS') && (bool) PHP_ZTS` trong [`bootstrap/app.php`](file:///c:/laragon/www/haomilk/bootstrap/app.php#L10-L13) để mọi thread đọc `.env` cô lập qua `$_ENV`/`$_SERVER`.

2. **Trùng mã đơn `DHxxxx` và mã phiếu nhập `PNxxxx` dưới InnoDB `REPEATABLE READ`:**
   - **Hiện tượng (Phase 1):** `STOCK-02` chỉ tạo `2/5` đơn, `STOCK-03` tạo `5/50` đơn (45 lỗi `Duplicate entry 'DH0001'`), `STOCK-09` dup webhook gặp `SQLSTATE[23000]` `HTTP 500`.
   - **Nguyên nhân:** [`Order::generateOrderCode()`](file:///c:/laragon/www/haomilk/app/Models/Order.php#L62) và [`StockReceipt::getNextCode()`](file:///c:/laragon/www/haomilk/app/Models/StockReceipt.php#L45) đọc `MAX(id)` trong transaction `REPEATABLE READ` khiến nhiều transaction đọc cùng snapshot `id`.
   - **Bản vá:** Thêm bảng & model [`CodeSequence`](file:///c:/laragon/www/haomilk/app/Models/CodeSequence.php#L9) sử dụng [`CodeSequence::nextCode()`](file:///c:/laragon/www/haomilk/app/Models/CodeSequence.php#L31) (khóa bi quan trên dòng PK cố định trong transaction), chuyển [`Order::generateOrderCode()`](file:///c:/laragon/www/haomilk/app/Models/Order.php#L62) và thêm [`StockReceipt::allocateNextCode()`](file:///c:/laragon/www/haomilk/app/Models/StockReceipt.php#L56).

3. **Cột `orders.order_status` ENUM thiếu trạng thái `'cancel_pending'` trên schema migration mới:**
   - **Hiện tượng (Phase 1):** `STOCK-08` và `STOCK-12` gặp `HTTP 500` (`SQLSTATE[01000]: Warning: 1265 Data truncated for column 'order_status'`).
   - **Bản vá:** Migration [`2026_11_12_100000_add_concurrency_sequences_and_status_fixes.php`](file:///c:/laragon/www/haomilk/database/migrations/2026_11_12_100000_add_concurrency_sequences_and_status_fixes.php) tạo bảng `code_sequences`, chuyển `orders.order_status` sang `VARCHAR(50)` trên MySQL và bổ sung index cho `payments.transaction_code`.

4. **`bootstrap/app.php` đọc `env('TRUSTED_PROXIES')` quá sớm trước khi `.env` nạp:**
   - **Hiện tượng (Phase 1):** `RateLimiter` gom mọi request sau reverse proxy về `127.0.0.1` (`HTTP 429` sau 10 lần login bất kể `X-Forwarded-For`).
   - **Bản vá:** Chuyển cấu hình sang [`config/trustedproxy.php`](file:///c:/laragon/www/haomilk/config/trustedproxy.php).

5. **`StockReceiptController::store()` lỗi `Undefined array key "notes"` & thiếu khóa dòng tồn kho:**
   - **Hiện tượng (Phase 1):** `STOCK-07` trả HTTP 500 khi dòng nhập kho không gửi field `notes`.
   - **Bản vá:** Chuẩn hóa lấy `$item['notes'] ?? null`, dùng `allocateNextCode()`, khóa `ProductVariant` theo `id` tăng dần ở `store()`, `update()`, `destroy()`.

6. **Thiếu `idempotency_key` khi submit checkout liên tiếp hoặc đồng thời:**
   - **Hiện tượng (Phase 1):** `STOCK-11` tạo trùng 2 `BankPaymentSession` khi double-submit VietQR.
   - **Bản vá:** Bổ sung `idempotency_key` với `Cache::lock` + cache kết quả trước khi kiểm tra giỏ hàng trong `CheckoutController`, cùng input UUID ẩn + khóa nút submit ở `resources/views/checkout/index.blade.php`.

7. **Webhook deadlock, cross-session replay và rò rỉ thông báo SQL khi exception 500:**
   - **Hiện tượng:** `PaymentWebhookController` từng trả lỗi thô SQL trong JSON khi gặp lỗi DB; hai webhook trùng `referenceCode` hoặc trùng session có nguy cơ xử lý đè hoặc deadlock khi khóa `ProductVariant`.
   - **Bản vá:** [`BankPaymentSession::completePayment()`](file:///c:/laragon/www/haomilk/app/Models/BankPaymentSession.php#L135) bọc `Cache::lock('hm_payment_tx_...')` + `DB::transaction(..., 3)` và khóa `ProductVariant` tăng dần theo `id`; `PaymentWebhookController` làm sạch phản hồi `500` và trả `200` an toàn cho tranh chấp hủy/hết hạn/hết tồn.

8. **Thiếu `lockForUpdate()` ở luồng hủy đơn, đổi địa chỉ, tất toán cọc & kiểm kho; và ép HTTPS scheme:**
   - **Bản vá:** Bổ sung `DB::transaction` + `lockForUpdate()` trong `AccountController` (`cancelOrder`, `updateOrderAddress`), `Admin\OrderController` (`updatePaymentStatus` — bảo toàn giao dịch cọc khi tất toán `delivered`, `bulkAction`), `Admin\InventoryController::adjust()`, `CheckoutController::cancelBankPayment()`, và chỉ bật `URL::forceScheme('https')` trong [`AppServiceProvider`](file:///c:/laragon/www/haomilk/app/Providers/AppServiceProvider.php#L24) khi `APP_ENV` thuộc `production`/`staging` và `APP_URL` dùng `https://`.

---

## 4. Kết Quả Chi Tiết Nhóm MySQL 8 Concurrency (`STOCK-01`..`STOCK-12`, `INV-01`)

| Test ID | Kịch bản & số request đồng thời (`curl_multi`) | Tồn trước -> sau | Đơn / TX DB | Min / Avg / P95 / Max (ms) | Lỗi 500/0 | Kết quả |
| --- | --- | --- | --- | --- | ---: | --- |
| `STOCK-01` | 2 khách mua đồng thời biến thể còn `1` chiếc | `1 -> 0` | `1` đơn, `1` item, `1` payment (`302`: 2, success: 1, other: 1) | `256.87 / 258.02 / 256.87 / 259.16` | 0 | **PASS** |
| `STOCK-02` | 10 khách cùng mua biến thể tồn `5` chiếc | `5 -> 0` | `5` đơn, `5` item, `5` payment (`302`: 10, success: 5, other: 5) | `340.33 / 417.88 / 445.97 / 446.06` | 0 | **PASS** |
| `STOCK-03` | 50 request checkout COD đồng thời (`100` tồn, `wall_time: 3193.49 ms`) | `100 -> 50` | `50` đơn, `50` mã `DHxxxx` duy nhất, `50` item, `50` payment | `998.23 / 2161.25 / 2961.72 / 3089.27` | 0 | **PASS** |
| `STOCK-04A` | 100 người dùng ảo đồng thời (50 `/`, 20 JSON SP, 15 giỏ hàng, 15 checkout; `wall_time: 3881.03 ms`) | `v4a: 200->192`, `v4b: 200->193` | `15/15` đơn (`200`: 85, `302`: 15) | `2912.40 / 3079.89 / 3620.69 / 3731.01` | 0 | **PASS** |
| `STOCK-04B` | 100 request checkout đồng thời cùng lúc (`wall_time: 5501.32 ms`) | `v4a: 192->142`, `v4b: 193->143` | Tổng `115/115` đơn sau 4A+4B (`302`: 100) | `2258.87 / 4248.85 / 5173.34 / 5302.51` | 0 | **PASS** |
| `STOCK-05` | 2 khách mua đồng thời 2 biến thể khác nhau (`qty 2` & `qty 3`) | `v5a: 5->3`, `v5b: 5->2` | `2` đơn (`302`: 2, success: 2) | `231.23 / 241.45 / 231.23 / 251.67` | 0 | **PASS** |
| `STOCK-06` | 10 request đồng thời cùng đặt giỏ 2 sản phẩm (mỗi SP tồn `6`) | `v6a: 6->0`, `v6b: 6->0` | `6` đơn, `12` order items (`302`: 10, success: 6, other: 4) | `370.65 / 462.50 / 507.29 / 507.60` | 0 | **PASS** |
| `STOCK-07` | Admin nhập kho `+10` (`PN0001`) đồng thời với 5 khách mua `1` SP | `5 -> 10` (`+10 - 5`) | `5` đơn, `imports: 10`, `sales: 5` (`302`: 6) | `274.43 / 329.74 / 361.36 / 387.25` | 0 | **PASS** |
| `STOCK-08` | Khách hủy (`cancel_pending`) rồi 2 request Admin hủy đơn đồng thời | `10 -> 10` | `final_status: cancelled`, `return_tx_count: 1` (không hoàn 2 lần) | `256.59 / 259.93 / 256.59 / 263.27` | 0 | **PASS** |
| `STOCK-09` | (a) Webhook thanh toán đấu `cancelBankPayment`; (b) 2 webhook trùng `FT_CONCURRENT_DUP2` vào 2 session khác nhau | (a) `5->5` (`cancelled`); (b) `10->9` | (a) 1 `200`, 1 `302`; (b) `[200, 409]`, `1` payment cho `FT_CONCURRENT_DUP2` | `183.28 / 198.52 / 183.28 / 213.75` | 0 | **PASS** |
| `STOCK-10` | Lỗi MySQL Trigger `SQLSTATE 45000` giữa transaction `order_items` | `10 -> 10` | `0` đơn, `0` items, `0` payments; không lộ `SQLSTATE` ra HTML | `HTTP 200` (redirect + flash an toàn) | 0 | **PASS** |
| `STOCK-11` | Double-submit đồng thời + retry với cùng `idempotency_key` (COD & QR) | `10 -> 9` | `1` đơn COD, `1` session QR; mọi `Location` trùng khớp | Redirect khớp 100% | 0 | **PASS** |
| `STOCK-12` | Admin xác nhận (đơn lẻ & bulk) đồng thời khách yêu cầu hủy / đổi địa chỉ | N/A | `order12a: confirmed`, `order12b: confirmed` (`302`: 4) | `228.52 / 235.80 / 241.21 / 241.36` | 0 | **PASS** |
| `INV-01` | Admin `POST /admin/inventory/adjust` (`actual_stock=10`) đồng thời với 3 checkout (`qty 1`) | `5 -> 10` (`tx_sum: +5`) | `3` đơn, `adj_tx_count: 1`, khớp bất biến `5 + tx_sum = 10` | `272.31 / 395.48 / 318.03 / 698.32` | 0 | **PASS** |

---

## 5. Kết Quả Nhóm SePay Webhook Integration (`WH-01`..`WH-15` Synthetic)

| Test ID | Kịch bản Webhook (`POST /api/payment/webhook`) | Mã GD / Nội dung test | HTTP Status | Trạng thái DB & Tồn kho | Kết quả |
| --- | --- | --- | ---: | --- | --- |
| `WH-01` | Ping `SEPAYTEST` | `code: SEPAYTEST` | 200 | `success: true`, không tạo đơn (`Order::count=0`) | **PASS** |
| `WH-02` | Thanh toán đủ VietQR (`250.000 đ`) | `id: 2002`, `FT_WH_02`, `HM200002` | 200 | Tạo `DH0001`, `payment_status=paid`, `order_status=pending`, tồn `20->19` | **PASS** |
| `WH-03` | Thanh toán cọc 40% đơn COD (`695.000 đ -> 278.000 đ`) | `id: 2003`, `FT_WH_03`, `HM200003` | 200 | Tạo `DH0002` (`cod`), `deposit_paid`, `paid=278000`, `remaining=417000`, `order_status=pending`, tồn `19->18` | **PASS** |
| `WH-04` | Thiếu header `Authorization` | `id: 2004`, `FT_WH_04` | 401 | Từ chối (fail-closed) | **PASS** |
| `WH-05` | Sai `Authorization` API Key (`wrong_token_abc`) | `id: 2005`, `FT_WH_05` | 401 | Từ chối | **PASS** |
| `WH-06` | Sai/giả mạo chữ ký `X-Sepay-Signature` HMAC-SHA256 | `id: 2006`, `FT_WH_06` | 401 | Từ chối | **PASS** |
| `WH-07` | Gửi lặp lại tuần tự cùng giao dịch đã thanh toán | `id: 2002`, `FT_WH_02`, `HM200002` | 200 | Idempotent: vẫn `2` đơn, tồn giữ `18` | **PASS** |
| `WH-08` | Replay `FT_WH_02` vào session mới đang `pending` | `id: 2008`, `FT_WH_02`, `HM200008` | 409 | Chặn replay, `HM200008` vẫn `pending` | **PASS** |
| `WH-09` | 2 webhook giống hệt gửi đồng thời vào cùng `HM200009` | `id: 2009`, `FT_WH_09`, `HM200009` | 200, 200 | Duy nhất `1` bản ghi payment `FT_WH_09`, tồn `18->17` | **PASS** |
| `WH-10` | Chuyển thiếu tiền (`240.000 < 250.000`) | `id: 2010`, `FT_WH_10`, `HM200010` | 200 | `success: false`, `HM200010` giữ nguyên `pending` | **PASS** |
| `WH-11` | Chuyển thừa tiền (`300.000 > 250.000`) | `id: 2011`, `FT_WH_11`, `HM200011` | 200 | `status=paid`, `order_id=4`, ghi chú đối soát dư `50.000 đ` | **PASS** |
| `WH-12` | Sai số tài khoản thụ hưởng (`9999999999`) | `id: 2012`, `FT_WH_12` | 422 | Từ chối payload | **PASS** |
| `WH-13` | Giao dịch tiền ra (`transferType: out`) | `id: 2013`, `FT_WH_13` | 422 | Từ chối payload | **PASS** |
| `WH-14` | Mã đơn không tồn tại (`HM999999`) | `id: 2014`, `FT_WH_14`, `HM999999` | 200 | `success: false`, không tạo đơn | **PASS** |
| `WH-15` | Thanh toán trễ sau khi session QR hết hạn (`-5` phút) | `id: 2015`, `FT_WH_15`, `HM200015` | 200 | Chuyển session sang `expired`, `order_id=null`, không tạo đơn | **PASS** |

---

## 6. Kiểm Thử Nghiệp Vụ COD Cọc 40%, Proxy, Queue & Cloudflare Tunnel

### 6.1. Nghiệp vụ COD có cọc 40% (`COD DEPOSIT`)
- **Đơn kiểm thử:** `#DH0002` (tổng `695.000 đ`, đã cọc 40% `278.000 đ`, còn thu `417.000 đ`).
- **Kết quả quan sát:**
  - `/admin/orders`: HTTP `200`, hiển thị `DH0002` trong danh sách ở trạng thái `Chờ xác nhận`.
  - `/admin/orders/{id}` & `/admin/orders/{id}/print`: HTTP `200`, hiển thị chính xác `278.000` (đã cọc) và `417.000` (còn thu).
  - Khi Admin chuyển trạng thái đơn sang `delivered`: `payment_status=paid`, `paid_amount=695000`, `remaining_amount=0`, tổng `payments` = `2` giao dịch có tổng `695.000 đ` (không ghi đè hay đếm trùng khoản cọc). -> **PASS**.

### 6.2. Reverse Proxy Rate Limit (`PROXY-01`) & Queue/Recovery (`QUEUE-01`)
- **`PROXY-01`:** Với `TRUSTED_PROXIES=127.0.0.1`, 6 lần đăng nhập sai cùng email từ `X-Forwarded-For: 203.0.113.10` cho `[302, 302, 302, 302, 302, 429]`; 11 lần từ cùng IP `203.0.113.11` với các email khác nhau cho `10 x 302`, lần 11 trả `429`; ngay sau đó `203.0.113.12` vẫn trả `302` (tách đúng IP khách thật sau proxy). -> **PASS**.
- **`QUEUE-01`:**
  - Giả lập ngoại lệ giữa `DB::transaction()` sau khi đã `decrement` tồn kho (`10 -> 7`) và chèn `DH9999` (`inserted_inside_tx=true`): rollback toàn bộ (`mid_tx_stock_after=10`, `mid_tx_orders_after=0`).
  - Push job vào bảng `jobs` trên MySQL (`jobs_before_work=1`), chạy `php artisan queue:work database --once --tries=1` -> `work_exit_code=0`, `jobs_after_work=0`, `marker_executed=true`.
  - Push job lỗi -> `failed_jobs_recorded=1` -> `php artisan queue:retry all` -> `failed_jobs_after_retry_all=0`, `requeued_jobs_count=1`.
  - `php artisan schedule:list`: `INFO No scheduled tasks have been defined.` (hệ thống hiện không định nghĩa cron job nền). -> **PASS**.

### 6.3. Cô Lập Webhook Origin (`TUNNEL-LOCAL-8012`) & Cloudflare Quick Tunnel HTTPS (`TUNNEL-CF-01`)
- Bật tạm Quick Tunnel HTTPS qua `cloudflared.exe tunnel --url http://127.0.0.1:8012 --no-autoupdate` trỏ duy nhất về VirtualHost `8012` (`APP_ENV=integration`, khóa mọi route khác bằng `RewriteRule`), cấp URL tạm `https://asia-transaction-increased-cooling.trycloudflare.com` (đã tắt ngay sau khi chạy xong).
- Kết quả gọi trực tiếp qua HTTPS Tunnel:
  - `GET /`, `GET /admin`, `GET /dang-nhap`, `POST /webhook/payment`, `GET /api/payment/webhook`, `GET /thanh-toan/chuyen-khoan/PAY-X8ACNESWJD/gia-lap-thanh-cong`: tất cả trả **`403 Forbidden`**.
  - `POST /api/payment/webhook` thiếu auth: **`401 Unauthorized`** (`850.1 ms`).
  - `POST /api/payment/webhook` sai HMAC `X-SePay-Signature`: **`401 Unauthorized`** (`615.64 ms`).
  - `POST /api/payment/webhook` hợp lệ (`HMxxxxxx`, `500.000 đ`): **`200 OK`** (`1089.2 ms`), trả về `"Xác nhận thanh toán và tạo đơn hàng #DH0001 thành công."`, `session_status=paid`, `order_code=DH0001`, tồn kho `10 -> 8`. -> **PASS**.

---

## 7. Hướng Dẫn Cấu Hình Staging / Production Webhook-Only & Checklist Live SePay

### 7.1. Nguyên tắc cấu hình Web Server (Nginx / Apache) trước Cloudflare Tunnel hoặc Internet
- Nếu dùng Cloudflare Tunnel chỉ để nhận webhook trong môi trường thử nghiệm/staging, trỏ tunnel vào một origin **chỉ mở `POST /api/payment/webhook`** với `APP_ENV=staging` hoặc `production` (tuyệt đối **không** dùng `APP_ENV=local`, **không** để route `/gia-lap-thanh-cong` hoạt động).
- Ví dụ rule Nginx cho vhost riêng chỉ mở webhook:
```nginx
location = /api/payment/webhook {
    limit_except POST { deny all; }
    try_files $uri /index.php?$query_string;
}
location / {
    return 403;
}
```
- Bắt buộc cấu hình `TRUSTED_PROXIES` đúng IP của reverse proxy / `cloudflared` (`127.0.0.1` nếu chạy local cùng host, hoặc dải IP proxy thật) để `RateLimiter` và logging nhận đúng IP client.
- Luôn chạy `php artisan config:cache` khi deploy lên VPS/Nginx+PHP-FPM để tránh parse `.env` ở runtime.

### 7.2. Checklist trước khi nối Live SePay Dashboard
1. Sao lưu toàn bộ database production trước khi chạy `php artisan migrate --force` (migration `2026_11_12_100000_add_concurrency_sequences_and_status_fixes` bổ sung bảng `code_sequences` và mở rộng `orders.order_status` sang `VARCHAR(50)`).
2. Xác nhận `.env` production có: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, `PAYMENT_WEBHOOK_TOKEN` và/hoặc `PAYMENT_WEBHOOK_HMAC_SECRET` đủ mạnh (hệ thống fail-closed nếu thiếu).
3. Nếu triển khai hệ thống có thêm tác vụ định kỳ trong tương lai (ví dụ tự động hủy phiên QR hết hạn), phải chạy cron `php artisan schedule:run` và worker `php artisan queue:work` dưới Supervisor/systemd. Hiện tại `schedule:list` trống vì các phiên quá hạn được đánh dấu `expired` ngay khi có truy vấn/polling/webhook đến.

---

## 8. Kết Luận Đánh Giá Sẵn Sàng

- **Trạng thái kỹ thuật hiện tại:** Toàn bộ **33/33** kiểm thử tích hợp thực tế trên MySQL 8 InnoDB + Apache đa luồng + SePay synthetic webhook + Cloudflare Tunnel HTTPS đều **PASS** (`0` HTTP 500 ngoài kiểm soát, `0` âm tồn kho, `0` hoàn tồn kép, `0` thanh toán trùng), cùng **`70/70` bài test PHPUnit (`337 assertions`) PASS** sau khi chuẩn hóa định dạng bằng Pint.
- **Đánh giá Staging:** Mã nguồn **ĐỦ ĐIỀU KIỆN ĐẨY STAGING**. Trước khi mở giao dịch tiền thật trên Production, chỉ còn 1 bước vận hành bắt buộc ngoài phạm vi kiểm thử tự động này là backup DB, chạy `migrate` trên server staging/production và thực hiện 1 giao dịch đối soát nhỏ trực tiếp trên Live SePay Dashboard do chủ hệ thống xác nhận.

