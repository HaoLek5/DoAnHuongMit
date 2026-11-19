# Hương Mít — Website Mẹ & Bé (E-Commerce Platform)

Hệ thống website thương mại điện tử chuyên cung cấp sản phẩm mẹ và bé **Hương Mít** (`huongmit.shop`), được phát triển trên nền tảng **Laravel 13**, **PHP 8.3**, tích hợp thanh toán chuyển khoản **VietQR**, tự động hóa webhook **SePay** và trợ lý tư vấn bán hàng thông minh **Google Gemini AI**.

---

## 1. Công nghệ Sử dụng (Tech Stack)

- **Backend:** Laravel 13.x, PHP 8.3
- **Cơ sở dữ liệu:** MySQL 8.x (InnoDB Engine)
- **Frontend:** Blade Templates, Tailwind CSS v4, Alpine.js, Vite
- **Thanh toán:** VietQR NAPAS 247, SePay Webhook Auto Reconciliation
- **Trí tuệ nhân tạo:** Google Gemini AI API (`gemini-3.5-flash-lite`)
- **Kiểm thử & Định dạng:** PHPUnit 12.x, Laravel Pint (PSR-12)

---

## 2. Hướng dẫn Cài đặt Môi trường Phát triển (Local Setup)

### Yêu cầu hệ thống:
- PHP >= 8.3 với các extension: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`.
- Composer 2.x
- Node.js >= 18.x & NPM
- MySQL 8.0+ (khuyến nghị chạy trên Laragon hoặc Docker)

### Các bước cài đặt:

1. **Clone repository và cài đặt thư viện PHP:**
   ```bash
   git clone <repository_url> haomilk
   cd haomilk
   composer install
   ```

2. **Cấu hình môi trường:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Mở file `.env` và điền thông tin kết nối MySQL database của bạn (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).*

3. **Chạy Migration & Seeder dữ liệu mẫu:**
   ```bash
   php artisan migrate
   php artisan db:seed --class=UserSeeder
   ```
   *(Mặc định tạo tài khoản Quản trị viên: `haopak2k5@gmail.com`)*

4. **Cài đặt thư viện Frontend & Build tài nguyên:**
   ```bash
   npm install
   npm run build
   ```

5. **Khởi động server phát triển:**
   ```bash
   composer run dev
   # Hoặc: php artisan serve
   ```
   Truy cập website tại: `http://localhost:8000` (hoặc `http://haomilk.test` trên Laragon).

---

## 3. Các Lệnh Vận hành Cơ bản (Operational Commands)

```bash
# Chạy toàn bộ bộ kiểm thử tự động (115 tests)
php artisan test --compact

# Định dạng mã nguồn theo chuẩn PSR-12 của dự án
vendor/bin/pint --format agent

# Xóa bộ nhớ đệm khi cập nhật cấu hình
php artisan optimize:clear

# Bật bộ nhớ đệm tối ưu hóa cho Production
php artisan optimize

# Kiểm tra danh sách route của hệ thống
php artisan route:list
```

---

## 4. Tài liệu Kỹ thuật Quan trọng

- [Kiến trúc Hệ thống & Quyết định Kỹ thuật (docs/ARCHITECTURE.md)](docs/ARCHITECTURE.md): Tổng quan kiến trúc MVC, bảo mật OTP, SePay idempotency, khóa đồng thời ConcurrencyLock, kiểm soát tồn kho và nghiệp vụ đơn cọc 40%.
- [Hướng dẫn Triển khai Lên AZDIGI Hosting (docs/DEPLOYMENT.md)](docs/DEPLOYMENT.md): Quy trình đưa ứng dụng lên AZDIGI cPanel shared hosting, cấu hình SMTP, Webhook SePay và cron job.

---

## 5. Giấy phép (License)

Dự án thuộc bản quyền sở hữu của Cửa hàng Mẹ & Bé Hương Mít. Mọi quyền được bảo lưu.
