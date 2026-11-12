<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trusted Reverse Proxies
    |--------------------------------------------------------------------------
    |
    | Đọc danh sách IP proxy tin cậy từ TRUSTED_PROXIES sau khi biến môi trường
    | đã được nạp đầy đủ (tránh đọc env() quá sớm trong bootstrap/app.php).
    |
    */
    'proxies' => env('TRUSTED_PROXIES'),
];
