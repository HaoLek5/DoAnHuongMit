# HƯỚNG DẪN TRIỂN KHAI (DEPLOYMENT GUIDE) — AZDIGI SHARED HOSTING

Tài liệu hướng dẫn chi tiết quy trình đưa website **Hương Mít** lên môi trường **AZDIGI Shared Hosting** (cPanel / LiteSpeed / CloudLinux), tên miền chính thức: `huongmit.shop`.

---

## 1. Yêu cầu Môi trường (Server Requirements)

- **PHP Version:** PHP `8.3` (chọn phiên bản PHP 8.3 trong cPanel -> *Select PHP Version*).
- **PHP Extensions:** `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `json`, `mbstring`, `openssl`, `pcre`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `zip`.
- **MySQL Version:** MySQL 8.0+ hoặc MariaDB 10.6+.
- **SSL Certificate:** Đã cài đặt chứng chỉ SSL (Let's Encrypt / AutoSSL) cho domain `huongmit.shop`.
- **Cấu hình Web Server Root:** Thư mục gốc website trỏ vào thư mục `public_html/public` (hoặc cấu hình `.htaccess` ngoài `public_html`).

---

## 2. Quy trình Triển khai Chi tiết

### Bước 1: Chuẩn bị Mã nguồn và Đẩy lên Server
1. Clone hoặc tải mã nguồn lên thư mục hosting (ví dụ: `/home/username/haomilk` hoặc `/home/username/public_html`).
2. Nếu Document Root trỏ trực tiếp vào `public_html`, đặt toàn bộ dự án vào thư mục gốc hosting (ngang hàng `public_html`), và symlink `public_html -> haomilk/public` (hoặc copy nội dung `public/` ra `public_html`).
3. Chạy cài đặt dependencies (nếu có SSH):
   ```bash
   composer install --no-dev --optimize-autoloader
   npm run build
   ```
   *(Hoặc build sẵn thư mục `public/build` từ máy local rồi upload lên server).*

---

### Bước 2: Thiết lập Biến Môi trường (.env)
Tạo file `.env` trên hosting từ mẫu [[.env.example](file:///c:/laragon/www/haomilk/.env.example)] và cấu hình các thông số:

```ini
APP_NAME="Hương Mít"
APP_ENV=production
APP_KEY=                          # Sinh bằng: php artisan key:generate
APP_DEBUG=false
APP_URL=https://huongmit.shop

# 1. Cơ sở dữ liệu MySQL (Tạo trên cPanel: MySQL Database & User)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cpaneluser_haomilk
DB_USERNAME=cpaneluser_dbuser
DB_PASSWORD="your_strong_db_password"

# 2. Xử lý hàng đợi (Queue)
# Khuyến nghị dùng 'sync' cho shared hosting AZDIGI để tránh tiến trình bị kill
QUEUE_CONNECTION=sync
CACHE_STORE=database
SESSION_DRIVER=database

# 3. Cấu hình Email SMTP (cPanel Webmail AZDIGI hoặc Gmail SMTP)
# Cách A: Email theo tên miền AZDIGI cPanel (Khuyến nghị)
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=mail.huongmit.shop
MAIL_PORT=465
MAIL_USERNAME=noreply@huongmit.shop
MAIL_PASSWORD="your_email_password"
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="noreply@huongmit.shop"
MAIL_FROM_NAME="Hương Mít"

# 4. Cấu hình Tài khoản Ngân hàng VietQR
BANK_ID=MB
BANK_NAME="Ngân hàng Quân Đội (MB Bank)"
BANK_ACCOUNT_NO=0369682734
BANK_ACCOUNT_NAME="CUA HANG HUONG MIT"
BANK_QR_TEMPLATE=compact2
BANK_PAYMENT_EXPIRY_MINUTES=10

# 5. Cấu hình Xác thực Webhook SePay
PAYMENT_WEBHOOK_TOKEN=your_sepay_api_key_here
PAYMENT_WEBHOOK_HMAC_SECRET=

# 6. Trợ lý AI Google Gemini
GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_MODEL=gemini-3.5-flash-lite
GEMINI_VERIFY_SSL=true
```

---

### Bước 3: Khởi tạo Cơ sở Dữ liệu & Khóa Ứng dụng
Chạy các lệnh khởi tạo qua giao diện cPanel Terminal hoặc SSH:

```bash
# 1. Tạo APP_KEY bí mật
php artisan key:generate

# 2. Chạy toàn bộ migrations trên database mới
php artisan migrate --force

# 3. Khởi tạo tài khoản Quản trị viên (UserSeeder)
php artisan db:seed --class=UserSeeder --force

# 4. Tạo liên kết lưu trữ ảnh sản phẩm
php artisan storage:link
```

---

### Bước 4: Cấu hình Webhook trên SePay Dashboard (`my.sepay.vn`)
1. Đăng nhập vào tài khoản SePay quản trị.
2. Điều hướng tới menu **Tích hợp Webhook** -> Bấm **Thêm Webhook**.
3. **Webhook URL:** Nhập chính xác:
   ```
   https://huongmit.shop/api/payment/webhook
   ```
4. **Phương thức gửi:** `POST`, Content-Type: `application/json`.
5. **Kiểu xác thực (Authentication):**
   - Chọn xác thực bằng **API Key**.
   - Sao chép mã API Key được cấp và điền vào biến `PAYMENT_WEBHOOK_TOKEN` trong `.env` trên hosting.
6. **Kiểm tra kết nối:** Bấm nút **Gửi Webhook Thử Nghiệm** trên SePay:
   - Nếu trả về HTTP `200` và `{"success": true, ...}`: Hệ thống đã kết nối hoàn hảo.

---

### Bước 5: Cấu hình Cron Job trên cPanel AZDIGI

Vào mục **Cron Jobs** trong cPanel, tạo tác vụ chạy định kỳ mỗi phút:

```bash
* * * * * /usr/local/bin/php /home/username/haomilk/artisan schedule:run >/dev/null 2>&1
```

*(Thay thế `/home/username/haomilk` bằng đường dẫn thư mục thực tế trên hosting của bạn).*

Nếu cấu hình `QUEUE_CONNECTION=database`, thêm cron chạy xử lý hàng đợi:
```bash
* * * * * /usr/local/bin/php /home/username/haomilk/artisan queue:work --stop-when-empty --tries=3 >/dev/null 2>&1
```

---

### Bước 6: Tối ưu Hóa Hiệu năng & Bộ Nhớ Đệm (Production Cache)

Trước khi công bố chính thức, chạy các lệnh cache để tăng tốc tải trang tối đa:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> [!WARNING]
> Mỗi khi chỉnh sửa nội dung file `.env` hoặc cập nhật code mới trên server, bạn bắt buộc phải chạy `php artisan config:clear` và `php artisan config:cache` lại để nhận cấu hình mới.

---

## 3. Checklist An toàn Khi Hoạt động (Security Checklist)

- [x] Đã tắt `APP_DEBUG=false` trong `.env`.
- [x] Đã chuyển `APP_ENV=production`.
- [x] Route giả lập thanh toán local (`/gia-lap-thanh-cong`) tự động bị vô hiệu hóa hoàn toàn trên production (HTTP 404).
- [x] Chứng chỉ HTTPS SSL hoạt động bình thường trên tên miền `huongmit.shop`.
- [x] File `.env` và các file cấu hình bí mật có quyền `chmod 600` (chỉ user sở hữu được đọc).
- [x] Toàn bộ mã OTP được hash bcrypt, rate-limit 5 lần/giờ theo email và 15 lần/giờ theo IP.
- [x] Webhook SePay chạy cơ chế fail-closed, chống replay attack và lọc thông tin tài khoản an toàn trong log.
