<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cấu hình Ngân hàng thụ hưởng & VietQR cho Cửa hàng
    |--------------------------------------------------------------------------
    |
    | Cung cấp thông tin tài khoản ngân hàng để tạo mã QR thanh toán động VietQR.
    | Các mã ngân hàng phổ biến: MB (MBBank), VCB (Vietcombank), ICB (VietinBank),
    | TCB (Techcombank), ACB (ACB), VPB (VPBank), BIDV (BIDV)...
    |
    */

    'bank_id' => env('BANK_ID', 'MB'),
    'bank_name' => env('BANK_NAME', 'Ngân hàng Quân Đội (MB Bank)'),
    'account_number' => env('BANK_ACCOUNT_NO', '0988888888'),
    'account_name' => env('BANK_ACCOUNT_NAME', 'CUA HANG HUONG MIT'),
    'template' => env('BANK_QR_TEMPLATE', 'compact2'), // compact2, compact, qr_only, print

    /*
    | Thời gian hết hạn của mã QR thanh toán (phút)
    */
    'expiry_minutes' => (int) env('BANK_PAYMENT_EXPIRY_MINUTES', 10),

    /*
    | Token xác thực cho Webhook từ bên thứ 3 (SePay, Casso,...)
    */
    'webhook_token' => env('PAYMENT_WEBHOOK_TOKEN'),

    /*
    | Secret xác thực chữ ký HMAC-SHA256 cho SePay Webhook (tùy chọn bổ sung)
    */
    'webhook_hmac_secret' => env('PAYMENT_WEBHOOK_HMAC_SECRET'),
];
