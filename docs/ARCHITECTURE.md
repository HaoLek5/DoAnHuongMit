# KIẾN TRÚC HỆ THỐNG — HƯƠNG MÍT (E-COMMERCE)

Tài liệu mô tả tổng quan kiến trúc, thiết kế phần mềm, các module lõi và các quyết định kỹ thuật quan trọng của website Thương mại Điện tử **Hương Mít** (`huongmit.shop`).

---

## 1. Công nghệ & Nền tảng (Technology Stack)

- **Framework:** Laravel 13.x (PHP 8.3+).
- **Cơ sở dữ liệu:** MySQL 8.x (InnoDB Engine, utf8mb4).
- **Frontend:** Blade Templates, Tailwind CSS v4, Alpine.js, Vite.
- **Tích hợp thanh toán:** VietQR (NAPAS 247) & SePay Webhook tự động.
- **AI Trợ lý:** Google Gemini API (`gemini-3.5-flash-lite`).
- **Môi trường:**
  - Phát triển: Laragon (Windows, Apache/Nginx, MySQL).
  - Production: AZDIGI Shared Hosting (CloudLinux LVE, LiteSpeed/Apache, PHP 8.3).

---

## 2. Kiến trúc Tổng thể & Các Module Chính

Hệ thống được tổ chức theo mô hình MVC hiện đại kết hợp Service Layer và Event/Job:

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AccountController.php          # Quản lý tài khoản, đổi mật khẩu OTP, sổ địa chỉ
│   │   ├── Auth/AuthController.php        # Đăng ký, đăng nhập, quên mật khẩu (OTP 6 số)
│   │   ├── CartController.php             # Quản lý giỏ hàng khách hàng & khách vãng lai
│   │   ├── CheckoutController.php         # Quy trình đặt hàng COD và khởi tạo VietQR
│   │   ├── PaymentWebhookController.php   # Xử lý Webhook SePay (Idempotent, HMAC, sổ cái)
│   │   └── Admin/                         # Cổng quản trị: Đơn hàng, Kho, Chiến dịch Sale, Thống kê
│   └── Middleware/
│       ├── AdminMiddleware.php            # Phân quyền quản trị viên cấp cao
│       └── EnsureEmailIsVerified.php      # Bắt buộc xác thực email OTP
├── Models/
│   ├── User.php                           # Người dùng (Customer / Admin)
│   ├── Product.php & ProductVariant.php  # Sản phẩm, biến thể, kiểm soát tồn kho
│   ├── Order.php & OrderItem.php          # Đơn hàng, chi tiết đơn, trạng thái thanh toán
│   ├── BankPaymentSession.php             # Phiên thanh toán VietQR (thời hạn 10 phút)
│   ├── BankTransaction.php                # Sổ cái đối soát ngân hàng (Ledger)
│   ├── EmailVerification.php              # Mã OTP hash bcrypt, rate limit kép (Email + IP)
│   └── CodeSequence.php                   # Cấp phát mã tuần tự không trùng lặp (DHxxxx, PNxxxx)
└── Services/
    ├── ConcurrencyLock.php                # Khóa đồng bộ đa tiến trình tầng database (GET_LOCK)
    ├── GeminiService.php                  # Trợ lý AI tư vấn sản phẩm mẹ và bé
    └── VietQrService.php                  # Tạo URL VietQR chuẩn NAPAS 247
```

---

## 3. Các Quyết định Kỹ thuật & Nghiệp vụ Quan trọng

### 3.1. Hệ thống Xác thực OTP An toàn (Email Authentication)
- **Mã hóa một chiều:** OTP 6 số được băm bằng `Hash::make()` trước khi lưu vào database. Không lưu văn bản thô (cleartext).
- **Sử dụng duy nhất 1 lần (Single-Use):** Bản ghi OTP bị xóa ngay lập tức khỏi database khi xác thực thành công.
- **Chống Brute-force:** Cho phép nhập sai tối đa 5 lần. Đến lần thứ 5, mã bị hủy tự động và yêu cầu tạo mã mới.
- **Giới hạn đa tầng (Dual-layer Rate Limiter):**
  - Tối đa 5 yêu cầu/giờ đối với mỗi Email.
  - Tối đa 15 yêu cầu/giờ đối với mỗi Địa chỉ IP (chặn bot tấn công phân tán nhiều email).
  - Cooldown bắt buộc 60 giây giữa các lần bấm gửi lại mã.
- **Bảo vệ tiêu đề thư:** Tiêu đề email trung tính, không chứa mã OTP thô để tránh rò rỉ qua màn hình khóa hoặc mail preview.

### 3.2. Cấp phát Mã Đơn hàng & Phiếu nhập tuần tự (`CodeSequence`)
- Dưới môi trường nhiều request đồng thời và chế độ cô lập `REPEATABLE READ` của MySQL InnoDB, việc dùng `MAX(id)` để tạo mã sẽ gây trùng lặp khóa chính.
- `CodeSequence` giải quyết bằng cách sử dụng khóa bi quan (`lockForUpdate()`) trên dòng bản ghi định danh cố định trong transaction (`CodeSequence::nextCode('orders', 'DH', 4)`), đảm bảo các mã `DH0001`, `DH0002`... cấp phát tuần tự và không bao giờ va chạm.

### 3.3. Tích hợp Thanh toán VietQR & SePay Webhook
- **Fail-Closed Authentication:** Từ chối mọi request nếu máy chủ chưa cấu hình secret token.
- **Đa phương thức xác thực:**
  - **API Key:** Hỗ trợ `Authorization: Apikey <TOKEN>`, `Authorization: Bearer <TOKEN>`, `X-Api-Key: <TOKEN>`.
  - **HMAC-SHA256:** Chuẩn SePay băm chuỗi `X-SePay-Timestamp . '.' . raw_body` với secret key.
  - **Chống Replay Attack:** Từ chối webhook có timestamp lệch quá 300 giây (5 phút).
- **Idempotency & Khóa đồng thời (`ConcurrencyLock`):**
  - Sử dụng MySQL Advisory Lock (`GET_LOCK` / `RELEASE_LOCK`) thông qua `ConcurrencyLock::run()`. Khóa độc lập với cache driver, hoạt động an toàn ngay cả khi hosting sử dụng `CACHE_STORE=file` hoặc `array`.
  - Kết hợp chỉ mục duy nhất `dedup_hash` trong bảng `bank_transactions`. Đảm bảo SePay gửi webhook trùng lặp không bao giờ tạo 2 đơn hàng hoặc trừ kho 2 lần.
- **Sổ cái đối soát toàn diện (`bank_transactions`):**
  Mọi dòng tiền chuyển vào tài khoản (dù đủ tiền, thiếu tiền, thừa tiền, trễ hạn 10 phút, hay sai cú pháp) đều được ghi nhận vào sổ cái với trạng thái rõ ràng (`matched`, `underpaid`, `overpaid`, `expired_late`, `unmatched_code`) để kế toán đối soát qua màn hình quản trị.

### 3.4. Nghiệp vụ Đơn hàng & Quản lý Tồn kho
- **Hai trạng thái xác nhận đơn hàng chuẩn hóa:** Hệ thống tinh gọn chỉ giữ 2 trạng thái xác nhận hiển thị:
  - **Chờ xác nhận** (`pending`): Đơn hàng mới tạo (đã thanh toán hoặc COD).
  - **Đã xác nhận** (`confirmed`): Đơn hàng nhân viên đã kiểm tra và đóng gói gửi hàng.
- **Đơn COD Đặt cọc 40%:**
  - Hệ thống hỗ trợ khách đặt cọc trước 40% qua VietQR để giữ hàng, 60% còn lại thanh toán COD khi nhận hàng.
  - Phân tách rõ ràng `$order->paid_amount` (40%) và `$order->remaining_amount` (60%) trên phiếu in và chi tiết đơn.
- **Trừ kho nguyên tử (Atomic Decrement):**
  - Trừ kho an toàn qua điều kiện `where('stock_quantity', '>=', $qty)->decrement('stock_quantity', $qty)` kết hợp khóa theo thứ tự ID biến thể tăng dần để tránh deadlock.
  - Hoàn trả kho và ghi nhận `InventoryTransaction` đúng 1 lần khi đơn hàng bị hủy.

### 3.5. Trợ lý AI Tư vấn Bán hàng (Google Gemini)
- Tích hợp Gemini 3.5 Flash Lite qua `GeminiService`.
- Giới hạn tần suất gọi bot (`throttle:chatbot`: 12 lượt/phút, 120 lượt/ngày) để kiểm soát chi phí API.
- Bắt buộc kiểm tra CSRF token và bật SSL verification trên production.

---

## 4. Bảo mật Ứng dụng (Security Controls)

1. **Phân quyền chặt chẽ (RBAC):** Middleware `AdminMiddleware` bảo vệ toàn bộ route `/admin`, ngăn chặn IDOR và leo thang đặc quyền.
2. **Miễn trừ CSRF có chọn lọc:** Chỉ miễn trừ duy nhất các webhook endpoint ngân hàng (`/api/payment/*`, `/webhook/*`), toàn bộ ứng dụng người dùng và chatbot bắt buộc CSRF token.
3. **An toàn Thread trên Apache mod_php Windows:** Sử dụng `Env::disablePutenv()` trong `bootstrap/app.php` nhằm ngăn ngừa race-condition của thư viện CRT C runtime khi chạy môi trường đa luồng.
4. **Bảo vệ Thông tin Nhạy cảm:**
   - Bộ lọc `sanitizeLogContext()` chỉ ghi 4 số cuối tài khoản ngân hàng, tuyệt đối không ghi token hay authorization header vào log.
   - Toàn bộ file cấu hình môi trường `.env` được loại trừ khỏi Git theo dõi.
